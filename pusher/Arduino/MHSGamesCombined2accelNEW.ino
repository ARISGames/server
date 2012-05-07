/*
Pin Layout of Multipurpose Arduino
 13:      Reserved for Ethernet Shield (not changeable)
 12:      Reserved for Ethernet Shield (not changeable)
 11:      Reserved for Ethernet Shield (not changeable)
 10:      Used for W5100               (not changeable)
 9:      Red LED
 8:      Yellow LED
 7:      Switch
 6:      Switch
 5:      Switch
 4:      Switch    
 3:      SA1 accelerometers interrupt pin and Switch (not changeable)
 2:      SA0 accelerometers interrupt pin and Switch (not changeable)
 1:      TX (unused)
 0:      RX (unused)
 14(A0):  Potentiometer
 15(A1):  Switch 
 16(A2):  Switch (not changeable)
 17(A3):  Switch Drill Button, Plunger Button, and regular button in barman
 18(A4):  5V on Arduino through 10K resistor and SCL(green) on accelerometer (not changeable)
 19(A5):  5V on Arduino through 10K resistor and SCL(white (and green)) on accelerometer (not changeable)
 
 */

#include <Ethernet.h>
#include <SPI.h>
#include "i2c.h"

#define THRESHOLD 0x50

const byte SCALE = 8;  // Sets full-scale range to +/-2, 4, or 8g. Used to calc real g values.
const byte dataRate = 0;  // 0=800Hz, 1=400, 2=200, 3=100, 4=50, 5=12.5, 6=6.25, 7=1.56

/*Ethernet variables*/
byte mac[] = { 
  0x90, 0xA2, 0xDA, 0x00, 0x49, 0xDE };
byte aris[] = { 
  50, 57, 125, 216 };

/*Pusher variables*/
String pusher_channel = "arduino-pusher_room_channel";
String pusher_event_register = "arduino_event_register";
String dynamite_pusher_event_num[] = { 
  "First_Slot_Filled", "Second_Slot_Filled", "Third_Slot_Filled", "Fourth_Slot_Filed", 
  "Fifth_Slot_Filled", "Sixth_Slot_Filled", "First_Slot_Empty", "Second_Slot_Empty", "Third_Slot_Empty", "Fourth_Slot_Empty", 
  "Fifth_Slot_Empty", "Sixth_Slot_Empty", "Collapsed", "Demolished"};
String barman_pusher_event_num[] = { 
  "0,0", "0,1", "0,2", "1,0", "1,1", "1,2", "2,0", "2,1", "2,2", "win", "collapse"};
String drill_pusher_event_num[] = {
  "Drill_On", "Drill_Off"};

/*Dynamite Game variables*/
const int intSA0Pin = 2;
const int intSA1Pin = 3;
const int plungerPin = 17;
int slotPin[] = { 
  16, 15, 7, 4, 5, 6};
const int explosionPin = 9;
boolean slotState[] = { 
  false, false, false, false, false, false};
boolean previousSlotState[] = { 
  false, false, false, false, false, false};
boolean collapse = false;
boolean plungerState = false;
boolean alreadyDestroyed = false;
boolean empty = true;
byte source;
boolean initializedDynamite = false;
const int timeDelay = 200;

/*Barman Game variables*/
String collapseArray[3][3] = {
  {
    "green", "green", "green"                  }
  ,
  {
    "green", "green", "green"                  }
  ,
  {
    "green", "green", "green"                  }
};
int ceilingPin[] = { 
  17, 16, 15, 7, 2, 3, 4, 5, 6};
const int crackingPin = 8;
const int collapsePin = 9;
boolean pressedArray[] = { 
  false, false, false, false, false, false, false, false, false};
boolean previouslyPressedArray[] = { 
  false, false, false, false, false, false, false, false, false};
boolean collapsed = false;
boolean initializedBarman = false;

/*Drill Game variables*/
const int drillPin = 17;
boolean drillState = false;
boolean previousDrillState = false;
boolean initializedDrill = false;

/*Global variables*/
const int potentiometerPin = 14;
int game;

EthernetClient arisClient;

void setup() {
  Ethernet.begin(mac); //,ip
  delay(1000);
  while(!pushMessage(pusher_channel, "arduino_event_register", "success")){ }
  byte c;
  c = readRegister(0x1C, 0x0D); 
  while(c != 0x2A){
    c = readRegister(0x1C, 0x0D);
  }
  if (c == 0x2A){ 
    initMMA8452(0x1C, SCALE, dataRate);  
  } 
  c = readRegister(0x1D, 0x0D);             //remove to get second accelerometer working, will break code otherwise
  while(c != 0x2A){
    c = readRegister(0x1D, 0x0D);
  }
  if (c == 0x2A){ 
    initMMA8452(0x1D, SCALE, dataRate);  
  } 

  pinMode(potentiometerPin, INPUT);
  randomSeed(analogRead(0));
}

void loop() {
  game = analogRead(potentiometerPin);
  while(game == 0){
    game = analogRead(potentiometerPin);
  }
  while(game >= 700){
    if(!initializedDynamite){
      pinMode(intSA0Pin, INPUT);                 
      digitalWrite(intSA0Pin, LOW);
      pinMode(intSA1Pin, INPUT);
      digitalWrite(intSA1Pin, LOW);
      pinMode(plungerPin, INPUT);  
      pinMode(explosionPin, OUTPUT);
      digitalWrite(explosionPin, LOW);  
      for(int i = 0; i < 6; i++){
        pinMode(slotPin[i], INPUT);
      }
    }
    initializedDynamite = true;

    empty = true;  
    readInputDynamite();
    actInputDynamite();
    if (digitalRead(intSA0Pin)==1){
      source = readRegister(0x1C, 0x0C);  // Read the interrupt source reg.
      if ((source & 0x08)==0x08){  //if tap register is set go check that
        byte secondSource = readRegister(0x1C, 0x22);  // Reads the PULSE_SRC register
        if (((secondSource & 0x10)==0x10) || ((source & 0x20)==0x20) || ((source & 0x40)==0x40)){
          collapse = true;
        }
      }
    }
    if (digitalRead(intSA1Pin)==1){
      source = readRegister(0x1D, 0x0C);  // Read the interrupt source reg.      //remove to get second accelerometer working, will break code otherwise
      if ((source & 0x08)==0x08){  //if tap register is set go check that
        byte secondSource = readRegister(0x1D, 0x22);  // Reads the PULSE_SRC register
        if (((secondSource & 0x10)==0x10) || ((source & 0x20)==0x20) || ((source & 0x40)==0x40)){
          collapse = true;
        }
      }
    }
  }
  initializedDynamite = false;
  while(game >= 400 && game <= 600){
    if(!initializedBarman){
      for(int i = 0; i < 9; i++){
        pinMode(ceilingPin[i], OUTPUT);
      }
      pinMode(crackingPin, OUTPUT);
      pinMode(collapsePin, OUTPUT);
      digitalWrite(crackingPin, LOW);
      digitalWrite(collapsePin, LOW);
    }
    initializedBarman = true;
    newCeiling();
    collapsed = false;
    while(!collapsed && game >= 400 && game <=600){
      readInputBarman();
      actInputBarman();
    }
  }
  initializedBarman = false;
  while(game <= 200){
    if(!initializedDrill){
      pinMode(drillPin, INPUT); 
    }
    initializedDrill = true;

    readInputDrill();
    actInputDrill();
  }
  initializedDrill = false;
}

void readInputDynamite(){
  plungerState = digitalRead(plungerPin);
  for(int i = 0; i < 6; i++){
    slotState[i] = digitalRead(slotPin[i]);
  }
}

void actInputDynamite(){
  for(int i = 0; i < 6; i++){
    if(slotState[i]){
      empty = false;
    }
    if(slotState[i] != previousSlotState[i]){
      int offset = 0;
      if(!slotState[i]){
        offset = 6;
      }
      while(!pushMessage(pusher_channel, dynamite_pusher_event_num[i+offset], "")){ }
    }
    previousSlotState[i] = slotState[i];
  }
  if(empty && alreadyDestroyed){
    reset();
  }
  if(collapse && !alreadyDestroyed){
    while(!pushMessage(pusher_channel, dynamite_pusher_event_num[12], "")){ }
      digitalWrite(explosionPin, HIGH);
      delay(timeDelay);
      digitalWrite(explosionPin, LOW);
    alreadyDestroyed = true;
  }
  if(plungerState && !alreadyDestroyed){
    while(!pushMessage(pusher_channel, dynamite_pusher_event_num[13], "")){ }
      digitalWrite(explosionPin, HIGH);
      delay(timeDelay);
      digitalWrite(explosionPin, LOW);
    alreadyDestroyed = true;
  }
}

boolean pushMessage(String channel, String event, String data){
  if(arisClient.connect(aris, 80)){
    String request = "GET /server/pusher/public_send.php?public_channel=";
    request += channel;
    request += "&public_event=";
    request += event;
    request += "&public_data=";
    request += data;
    request += " HTTP/1.0";
    arisClient.println(request);
    arisClient.println();
    arisClient.stop();
    return true;
  }
  return false;
}

/* Initialize the MMA8452 registers */
void initMMA8452(unsigned char MMA8452_ADDRESS, byte fsr, byte dataRate)
{
  MMA8452Standby(MMA8452_ADDRESS);  // Must be in standby to change registers

  /* Set up the full scale range to 2, 4, or 8g. */
  if ((fsr==2)||(fsr==4)||(fsr==8))
    writeRegister(MMA8452_ADDRESS, 0x0E, fsr >> 2);  
  else
    writeRegister(MMA8452_ADDRESS, 0x0E, 0);

  /* Setup the 3 data rate bits, from 0 to 7 */
  writeRegister(MMA8452_ADDRESS, 0x2A, readRegister(MMA8452_ADDRESS, 0x2A) & ~(0x38));
  if (dataRate <= 7)
    writeRegister(MMA8452_ADDRESS, 0x2A, readRegister(MMA8452_ADDRESS, 0x2A) | (dataRate << 3));  

  writeRegister(MMA8452_ADDRESS, 0x11, 0x40);  // 1. Enable P/L
  writeRegister(MMA8452_ADDRESS, 0x13, 0x44);  // 2. 29deg z-lock (don't think this register is actually writable)
  writeRegister(MMA8452_ADDRESS, 0x14, 0x84);  // 3. 45deg thresh, 14deg hyst (don't think this register is writable either)
  writeRegister(MMA8452_ADDRESS, 0x12, 0x50);  // 4. debounce counter at 100ms (at 800 hz)

  writeRegister(MMA8452_ADDRESS, 0x21, 0x7F);  // 1. enable single/double taps on all axes
  writeRegister(MMA8452_ADDRESS, 0x23, THRESHOLD);  // 2. x thresh at THRESHOLD, multiply the value by 0.0625g/LSB to get the threshold
  writeRegister(MMA8452_ADDRESS, 0x24, THRESHOLD);  // 2. y thresh at THRESHOLD, multiply the value by 0.0625g/LSB to get the threshold
  writeRegister(MMA8452_ADDRESS, 0x25, THRESHOLD);  // 2. z thresh at THRESHOLD, multiply the value by 0.0625g/LSB to get the threshold
  writeRegister(MMA8452_ADDRESS, 0x26, 0x30);  // 3. 30ms time limit at 800Hz odr, this is very dependent on data rate, see the app note
  writeRegister(MMA8452_ADDRESS, 0x27, 0xA0);  // 4. 200ms (at 800Hz odr) between taps min, this also depends on the data rate
  writeRegister(MMA8452_ADDRESS, 0x28, 0xFF);  // 5. 318ms (max value) between taps max

  /* Set up interrupt 1 and 2 */
  writeRegister(MMA8452_ADDRESS, 0x2C, 0x02);  // Active high, push-pull interrupts
  writeRegister(MMA8452_ADDRESS, 0x2D, 0x19);  // DRDY, P/L and tap ints enabled
  writeRegister(MMA8452_ADDRESS, 0x2E, 0x01);  // DRDY on INT1, P/L and taps on INT2

  MMA8452Active(MMA8452_ADDRESS);  // Set to active to start reading
}

/* Sets the MMA8452 to standby mode.
 It must be in standby to change most register settings */
void MMA8452Standby(unsigned char MMA8452_ADDRESS){
  byte c = readRegister(MMA8452_ADDRESS, 0x2A);
  writeRegister(MMA8452_ADDRESS, 0x2A, c & ~(0x01));
}

/* Sets the MMA8452 to active mode.
 Needs to be in this mode to output data */
void MMA8452Active(unsigned char MMA8452_ADDRESS){
  byte c = readRegister(MMA8452_ADDRESS, 0x2A);
  writeRegister(MMA8452_ADDRESS, 0x2A, c | 0x01);
}

/* Read i registers sequentially, starting at address 
 into the dest byte arra */
void readRegisters(unsigned char MMA8452_ADDRESS, byte address, int i, byte * dest){
  i2cSendStart();
  i2cWaitForComplete();

  i2cSendByte((MMA8452_ADDRESS<<1));	// write 0xB4
  i2cWaitForComplete();

  i2cSendByte(address);	// write register address
  i2cWaitForComplete();

  i2cSendStart();
  i2cSendByte((MMA8452_ADDRESS<<1)|0x01);	// write 0xB5
  i2cWaitForComplete();
  for (int j=0; j<i; j++)
  {
    i2cReceiveByte(TRUE);
    i2cWaitForComplete();
    dest[j] = i2cGetReceivedByte();	// Get MSB result
  }
  i2cWaitForComplete();
  i2cSendStop();

  cbi(TWCR, TWEN);	// Disable TWI
  sbi(TWCR, TWEN);	// Enable TWI
}

/* read a single byte from address and return it as a byte */
byte readRegister(unsigned char MMA8452_ADDRESS, uint8_t address){
  byte data;

  i2cSendStart();
  i2cWaitForComplete();

  i2cSendByte((MMA8452_ADDRESS<<1));	// write 0xB4
  i2cWaitForComplete();

  i2cSendByte(address);	// write register address
  i2cWaitForComplete();

  i2cSendStart();

  i2cSendByte((MMA8452_ADDRESS<<1)|0x01);	// write 0xB5
  i2cWaitForComplete();
  i2cReceiveByte(TRUE);
  i2cWaitForComplete();

  data = i2cGetReceivedByte();	// Get MSB result
  i2cWaitForComplete();
  i2cSendStop();

  cbi(TWCR, TWEN);	// Disable TWI
  sbi(TWCR, TWEN);	// Enable TWI

  return data;
}

/* Writes a single byte (data) into address */
void writeRegister(unsigned char MMA8452_ADDRESS, unsigned char address, unsigned char data){
  i2cSendStart();
  i2cWaitForComplete();

  i2cSendByte((MMA8452_ADDRESS<<1));// write 0xB4
  i2cWaitForComplete();

  i2cSendByte(address);	// write register address
  i2cWaitForComplete();

  i2cSendByte(data);
  i2cWaitForComplete();

  i2cSendStop();
}

void reset(){
  collapse = false;
  alreadyDestroyed =false;
  plungerState = false;
}

void newCeiling(){
  for(int i = 0; i < 3; i++){
    for(int j = 0; j < 3; j++){
      collapseArray[i][j] = "green";
    }
  }
  for(int i = 0; i < 9; i++){
    pressedArray[i] = false;
  }
  int row = random(3);
  int col  = random(3);
  collapseArray[row][col] = "red";
  for(int i = 0; i < 3; i++){
    for(int j = 0; j < 3; j++){
      if(i - 1 >= 0){
        if(collapseArray[i-1][j] == "red"){
          collapseArray[i][j] = "yellow";
        }
      }
      if(i + 1 < 3){
        if(collapseArray[i+1][j] == "red"){
          collapseArray[i][j] = "yellow";
        }
      }
      if(j - 1 >= 0){
        if(collapseArray[i][j-1] == "red"){
          collapseArray[i][j] = "yellow";
        }
      }
      if(j + 1 < 3){
        if(collapseArray[i][j+1] == "red"){
          collapseArray[i][j] = "yellow";
        }
      }
    }
  }
}

void readInputBarman(){
  for(int i = 0; i < 9; i++){
    if(!pressedArray[i]){
      pressedArray[i] = digitalRead(ceilingPin[i]);
    }
  }
}

void actInputBarman(){
  for(int i = 0; i < 9; i++){
    if(pressedArray[i] && !previouslyPressedArray[i]){
      if(i<3){
        while(!pushMessage(pusher_channel, barman_pusher_event_num[i], collapseArray[0][i])){ }
          if(collapseArray[0][i] == "red"){
            if(checkGameResult()){
              while(!pushMessage(pusher_channel, barman_pusher_event_num[9], "")){ }
            }
            else{
              while(!pushMessage(pusher_channel, barman_pusher_event_num[10], "")){ }
            }
            digitalWrite(collapsePin, HIGH);
            delay(timeDelay);                                   //edit delay time
            digitalWrite(collapsePin, LOW);
          }
          if(collapseArray[0][i] == "yellow"){
            digitalWrite(crackingPin, HIGH);
            delay(timeDelay);                                 //edit delay time
            digitalWrite(crackingPin, LOW);
          }
      }
      else if(i<6){
        while(!pushMessage(pusher_channel, barman_pusher_event_num[i], collapseArray[1][i-3])){ }
          if(collapseArray[1][i-3] == "red"){
            if(checkGameResult()){
              while(!pushMessage(pusher_channel, barman_pusher_event_num[9], "")){ }
            }
            else{
              while(!pushMessage(pusher_channel, barman_pusher_event_num[10], "")){ }
            }
            digitalWrite(collapsePin, HIGH);
            delay(timeDelay);                                   //edit delay time
            digitalWrite(collapsePin, LOW);
          }
          if(collapseArray[1][i-3] == "yellow"){
            digitalWrite(crackingPin, HIGH);
            delay(timeDelay);                                 //edit delay time
            digitalWrite(crackingPin, LOW);
          }
      }
      else{
        while(!pushMessage(pusher_channel, barman_pusher_event_num[i], collapseArray[2][i-6])){ }
          if(collapseArray[2][i-6] == "red"){
            if(checkGameResult()){
              while(!pushMessage(pusher_channel, barman_pusher_event_num[9], "")){ }
            }
            else{
              while(!pushMessage(pusher_channel, barman_pusher_event_num[10], "")){ }
            }
            digitalWrite(collapsePin, HIGH);
            delay(timeDelay);                                   //edit delay time
            digitalWrite(collapsePin, LOW);
          }
          if(collapseArray[2][i-6] == "yellow"){
            digitalWrite(crackingPin, HIGH);
            delay(timeDelay);                                 //edit delay time
            digitalWrite(crackingPin, LOW);
          }
      }
    } 
    previouslyPressedArray[i] = pressedArray[i];
  }
}

boolean checkGameResult(){
  boolean result = true;
  for(int i = 0; i < 9; i++){
    if(!pressedArray[i]){
      result = false; 
    }
  }
  collapsed = true;
  return result;
}

void readInputDrill(){
  drillState = digitalRead(drillPin);
}

void actInputDrill(){
  if(drillState != previousDrillState){
    int offset = 0;
    if(!drillState){
      offset = 1;
    }
    while(!pushMessage(pusher_channel, drill_pusher_event_num[offset], "")){ }
    previousDrillState = drillState;
  }
}




