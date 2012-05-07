/*
Pin Layout of Dynamite Game Arduino
 13:      Reserved for Ethernet Shield (not changeable)
 12:      Reserved for Ethernet Shield (not changeable)
 11:      Reserved for Ethernet Shield (not changeable)
 10:      Used for W5100               (not changeable)
 9:      Rumble Packs
 8:      Switch
 7:      Switch
 6:      Switch
 5:      Switch
 4:      Relay   
 3:      SA1 accelerometers interrupt pin and Switch (not changeable)
 2:      SA0 accelerometers interrupt pin and Switch (not changeable)
 1:      TX (unused)
 0:      RX (unused)
 14(A0):  Unused
 15(A1):  Switch 
 16(A2):  Switch
 17(A3):  Plunger Button
 18(A4):  5V on Arduino through 10K resistor and SCL(green) on accelerometer (not changeable)
 19(A5):  5V on Arduino through 10K resistor and SCL(white (and green)) on accelerometer (not changeable)
 
 */

#include <Ethernet.h>
#include <SPI.h>
#include "i2c.h"

#define THRESHOLD 0x50

const byte SCALE = 8;  // Sets full-scale range to +/-2, 4, or 8g. Used to calc real g values.
const byte dataRate = 0;  // 0=800Hz, 1=400, 2=200, 3=100, 4=50, 5=12.5, 6=6.25, 7=1.56

const int intSA0Pin = 2;  // These can be changed, 2 and 3 are the Arduinos ext int pins
const int intSA1Pin = 3;
const int relayPin1 = 4;

byte mac[] = { 
  0x90, 0xA2, 0xDA, 0x06, 0x00, 0xD8 };
byte aris[] = { 
  50, 56, 80, 147 };

String pusher_channel = "arduino-pusher_room_channel";
String pusher_event_register = "arduino_event_register";
String pusher_event_num[] = { 
  "First_Slot_Filled", "Second_Slot_Filled", "Third_Slot_Filled", "Fourth_Slot_Filed", 
  "Fifth_Slot_Filled", "Sixth_Slot_Filled", "First_Slot_Empty", "Second_Slot_Empty", "Third_Slot_Empty", "Fourth_Slot_Empty", 
  "Fifth_Slot_Empty", "Sixth_Slot_Empty", "Accidental_Explosion", "Plunger_Pushed"};

const int explosionPin = 9;
const int plungerPin = 17;
int slotPin[] = { 
  5, 6, 7, 8, 15, 16};
boolean slotState[] = { 
  false, false, false, false, false, false};
boolean previousSlotState[] = { 
  false, false, false, false, false, false};
boolean collapse = false;
boolean plungerState = false;
boolean alreadyDestroyed = false;
boolean empty = true;
byte source;

EthernetClient arisClient;

void setup() {
  Serial.begin(115200);
  Ethernet.begin(mac);
  delay(1000); //Give time to initialize before connecting
  while(!pushMessage(pusher_channel, "arduino_event_register", "success")){ }

  /* Set up the interrupt pins, they're set as active high, push-pull */
  pinMode(intSA0Pin, INPUT);
  digitalWrite(intSA0Pin, LOW);
  pinMode(intSA1Pin, INPUT);
  digitalWrite(intSA1Pin, LOW);

  byte c;
  c = readRegister(0x1C, 0x0D); 
  Serial.println(c,HEX);
  //while(c != 0x2A){
    Serial.println(c, HEX);
    Serial.println("first");
    c = readRegister(0x1C, 0x0D);
  //}
  initMMA8452(0x1C, SCALE, dataRate);  
  c = readRegister(0x1D, 0x0D);
  Serial.println(c, HEX);
  while(c != 0x2A){
    Serial.println(c, HEX);
    Serial.println("second");
    c = readRegister(0x1D, 0x0D);
  }
  initMMA8452(0x1D, SCALE, dataRate);  

  pinMode(plungerPin, INPUT);
  pinMode(relayPin1, OUTPUT);
  digitalWrite(explosionPin, LOW);  
  for(int i = 0; i < 6; i++)
    pinMode(slotPin[i], INPUT);

}

void loop() {
  empty = true;  
  readInput();
  actInput();
  if (digitalRead(intSA0Pin)==1){
    source = readRegister(0x1C, 0x0C);
    if ((source & 0x08)==0x08){
      byte secondSource = readRegister(0x1C, 0x22);
      if (((secondSource & 0x10)==0x10) || ((source & 0x20)==0x20) || ((source & 0x40)==0x40)){
        collapse = true;
      }
    }
  }
  if (digitalRead(intSA1Pin)==1){
    source = readRegister(0x1D, 0x0C);
    if ((source & 0x08)==0x08){
      byte secondSource = readRegister(0x1D, 0x22);
      if (((secondSource & 0x10)==0x10) || ((source & 0x20)==0x20) || ((source & 0x40)==0x40)){
        collapse = true;
      }
    }
  }
}

void readInput(){
  plungerState = digitalRead(plungerPin);
  for(int i = 0; i < 6; i++){
    slotState[i] = digitalRead(slotPin[i]);
  }
}

void actInput(){
  for(int i = 0; i < 6; i++){
    if(slotState[i]){
      empty = false;
    }
    if(slotState[i] != previousSlotState[i]){
      int offset = 0;
      if(!slotState[i]){
        offset = 6;
      }
      while(!pushMessage(pusher_channel, pusher_event_num[i+offset], "")){ }
    }
    previousSlotState[i] = slotState[i];
  }
  if(collapse && !alreadyDestroyed){
    while(!pushMessage(pusher_channel, pusher_event_num[12], "")){ }
      digitalWrite(relayPin1, HIGH);
      delay(500);
      digitalWrite(relayPin1, LOW);
    alreadyDestroyed = true;
  }
  if(plungerState && !alreadyDestroyed){
    while(!pushMessage(pusher_channel, pusher_event_num[13], "")){ }
      digitalWrite(relayPin1, HIGH);
      delay(500);
      digitalWrite(relayPin1, LOW);
    alreadyDestroyed = true;
  }
  if(empty && alreadyDestroyed){
    reset();
  }
}

boolean pushMessage(String channel, String event, String data){
  if(arisClient.connect(aris, 80)){
    String request = "GET /server/pusher/public_send.php?channel=";
    request += channel;
    request += "&event=";
    request += event;
    request += "&data=";
    request += data;
    request += " HTTP/1.0";
    arisClient.println(request);
    arisClient.println();
    arisClient.stop();
    return true;
  }
  return false;
}

void reset(){
  collapse = false;
  alreadyDestroyed =false;
  plungerState = false;
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

