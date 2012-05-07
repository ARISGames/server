#include <Ethernet.h>
#include <SPI.h>

String collapseArray[3][3] = {
  {
    "green", "green", "green"            }
  ,
  {
    "green", "green", "green"            }
  ,
  {
    "green", "green", "green"            }
};

byte mac[] = { 
  0x90, 0xA2, 0xDA, 0x00, 0x49, 0xDE };
byte aris[] = { 
  50, 57, 125, 216 };

String pusher_channel = "arduino-pusher_room_channel";
String pusher_event_register = "arduino_event_register";
String pusher_event_num[] = { 
  "0,0", "0,1", "0,2", "1,0", "1,1", "1,2", "2,0", "2,1", "2,2", "win", "collapse"};

EthernetClient arisClient;

int ceilingPin[] = { 
  17, 16, 15, 7, 2, 3, 4, 5, 6};
int crackingPin = 8;
int collapsePin = 9;
boolean pressedArray[] = { 
  false, false, false, false, false, false, false, false, false};
boolean previouslyPressedArray[] = { 
  false, false, false, false, false, false, false, false, false};
boolean collapsed = false;

void setup(){
  Ethernet.begin(mac); //,ip
  delay(1000); //Give time to initialize before connecting
  while(!pushMessage(pusher_channel, "arduino_event_register", "success")) { }
  randomSeed(analogRead(0));
  for(int i = 0; i < 9; i++){
    pinMode(ceilingPin[i], OUTPUT);
  }
  pinMode(crackingPin, OUTPUT);
  pinMode(collapsePin, OUTPUT);
  digitalWrite(crackingPin, LOW);
  digitalWrite(collapsePin, LOW);
}

void loop(){
  newCeiling();
  collapsed = false;
  while(!collapsed){
    readInputBarman();
    actInputBarman();
  }
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
        while(!pushMessage(pusher_channel, pusher_event_num[i], collapseArray[0][i])){ }
          if(collapseArray[0][i] == "red"){
            if(checkGameResult()){
              while(!pushMessage(pusher_channel, pusher_event_num[9], "")){ }
            }
            else{
              while(!pushMessage(pusher_channel, pusher_event_num[10], "")){ }
            }
            digitalWrite(collapsePin, HIGH);
            delay(500);                                   //edit delay time
            digitalWrite(collapsePin, LOW);
          }
          if(collapseArray[0][i] == "yellow"){
            digitalWrite(crackingPin, HIGH);
            delay(500);                                 //edit delay time
            digitalWrite(crackingPin, LOW);
          }
      }
      else if(i<6){
        while(!pushMessage(pusher_channel, pusher_event_num[i], collapseArray[1][i-3])){ } 
          if(collapseArray[1][i-3] == "red"){
            if(checkGameResult()){
              while(!pushMessage(pusher_channel, pusher_event_num[9], "")){ }
            }
            else{
              while(!pushMessage(pusher_channel, pusher_event_num[10], "")){ }
            }
            digitalWrite(collapsePin, HIGH);
            delay(500);                                   //edit delay time
            digitalWrite(collapsePin, LOW);
          }
          if(collapseArray[1][i-3] == "yellow"){
            digitalWrite(crackingPin, HIGH);
            delay(500);                                 //edit delay time
            digitalWrite(crackingPin, LOW);
          }
      }
      else{
        while(!pushMessage(pusher_channel, pusher_event_num[i], collapseArray[2][i-6])){ }
          if(collapseArray[2][i-6] == "red"){
            if(checkGameResult()){
              while(!pushMessage(pusher_channel, pusher_event_num[9], "")){ }
            }
            else{
              while(!pushMessage(pusher_channel, pusher_event_num[10], "")){ }
            }
            digitalWrite(collapsePin, HIGH);
            delay(500);                                   //edit delay time
            digitalWrite(collapsePin, LOW);
          }
          if(collapseArray[2][i-6] == "yellow"){
            digitalWrite(crackingPin, HIGH);
            delay(500);                                 //edit delay time
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
