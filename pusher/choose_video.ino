#include <PusherClient.h>
#include <Ethernet.h>
#include <SPI.h>

char subscribe[] = "choose_video";
char receive_channel[] = "choose_video";
String pusher_channel = "choose_video";
String pusher_event_register = "arduino_event_register";
String fact[] = {"a", "b","c", "JacobHanshaw"};
int fact_delays[] = { 4000, 4000, 4000, 4000};
int facts = 4;

EthernetClient arisClient;
PusherClient pusherClient;

byte mac[] = { 
  0x90, 0xA2, 0xDA, 0x0C, 0x00, 0x83 };
byte aris[] = { 
  50, 56, 80, 147 };

const int relayPin1 = 2;
const int relayPin2 = 3;
const int video_over = 6;

int video1Votes = 0;
int video2Votes = 0;
int members = 0;

void setup() {
  //Serial.begin(9600);
  Serial.println("Setup...");
  Ethernet.begin(mac); //,ip
  delay(1000); //Give time to initialize before connecting

  pinMode(relayPin1, OUTPUT);
  pinMode(relayPin2, OUTPUT);
  //pinMode(video_over, INPUT);

  while(!pusherClient.connect("7fe26fe9f55d4b78ea02")){}
  while(!pushMessage(pusher_channel, pusher_event_register, "success")) {}
  pusherClient.bind("Member_Added", memberAdd);
  pusherClient.bind("Member_Removed", memberRemove);
  pusherClient.bind("Cast_Vote", eventHandler);
  //  pusherClient.bindAll(eventHandler);
  //  pusherClient.bind(subscribe, eventHandler);
  pusherClient.subscribe(receive_channel);
  Serial.println("Pusher Connect Success!");
} 


void loop() {
  pusherClient.monitor();
  if(members > 0){
    //Begin the login time
    long startTime = millis();
    while((millis() -startTime) <= 15000){
      pusherClient.monitor();
      int time = (millis()-startTime);
      if(time % 1000 <= 10){
        String output = (String)time;
        while(!pushMessage(pusher_channel, "Clock_Time", output)) { }
      }
    }
    //Begin Voting
    while(!pushMessage(pusher_channel, "Begin_Voting", "")) { }
    startTime = millis();
    while((millis() -startTime) <= 30000 && members != (video1Votes + video2Votes)){
      pusherClient.monitor();
    }
    //Voting Complete, trigger the pin
    if(video1Votes >= video2Votes){
      digitalWrite(relayPin1, HIGH);
      delay(500);
      digitalWrite(relayPin1, LOW);
      Serial.println("Video 1 Wins!");
    }
    else{
      digitalWrite(relayPin2, HIGH);
      delay(500);
      digitalWrite(relayPin2, LOW);
      Serial.println("Video 2 Wins!");
    }
    video1Votes = 0;
    video2Votes = 0;
    
    //Tell clients, Voting is complete
    while(!pushMessage(pusher_channel, "Done_Voting", "")) { 
    }
    delay(2000);
    for(int i = 0; i < facts; i++){
       while(!pushMessage(pusher_channel, "Message", fact[i])) { }
       delay(fact_delays[i]);
    }
    //while(digitalRead(video_over) == LOW) { }
    while(!pushMessage(pusher_channel, "Video_Over", "")) { 
    }
  }
}

void memberAdd(String data) {
  members++;
 // Serial.println("Members: ");
 // Serial.println(members);
}

void memberRemove(String data) {
  if(members-1>=0){
    members--;
  }
//  Serial.println("Members: ");
//  Serial.println(members);
}

void eventHandler(String data) {
  //Serial.println(data);
  int firstColon = data.indexOf(':');
  int secondColon = data.indexOf(':', firstColon + 1 );
  int start = secondColon + 4;
  int endQuote = data.indexOf('"', start);
  int endofData = endQuote -1;
  String importantData = data.substring(start, endofData);
 // Serial.println(importantData);
  if(importantData.charAt(0) == 'v'){
    importantData = importantData.substring(1);
    char pointer[importantData.length()];
    for(int i = 0; i < importantData.length(); i++){
      pointer[i] = importantData.charAt(i);
    }
    int vote = atoi(pointer);
    if(vote == 1){
    //  Serial.println("1");
      video1Votes++;
      String votes = (String)video1Votes;
      votes += "^";
      votes += video2Votes;
      Serial.println(votes);
      while(!pushMessage(pusher_channel, "Vote", votes)) {  
      }
    }
    else if(vote == 2){
  //    Serial.println("2");
      video2Votes++;
      String votes = (String)video1Votes;
      votes += "^";
      votes += video2Votes;
      Serial.println(votes);
      while(!pushMessage(pusher_channel, "Vote", votes)) { 
      }
    }
  }
  else{
  //  Serial.println("ERROR: " + data);
  }
}

boolean pushMessage(String channel, String event, String data){
  if(arisClient.connect(aris, 80)){
    String request = "GET /server/pusher/public_send.php?channel="; //change when updated
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

