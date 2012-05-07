#include <PusherClient.h>
#include <Ethernet.h>
#include <SPI.h>

char receive_channel[] = "choose-video";
String pusher_channel = "choose-video";

EthernetClient arisClient;
PusherClient pusherClient;

byte mac[] = { 
  0x90, 0xA2, 0xDA, 0x0C, 0x00, 0x83 };
byte aris[] = { 
  50, 56, 80, 147 };

const int relayPin1 = 2;
const int relayPin2 = 3;
const int relayPin3 = 4;
const int relayPin4 = 5;
const int video_over = 19;

boolean previousState = false;

void setup() {
  Serial.begin(9600);
  Serial.println("Setup...");
  Ethernet.begin(mac); //,ip
  delay(1000); //Give time to initialize before connecting

  pinMode(relayPin1, OUTPUT);
  pinMode(relayPin2, OUTPUT);
  pinMode(relayPin3, OUTPUT);
  pinMode(relayPin4, OUTPUT);
  pinMode(video_over, INPUT);
  digitalWrite(video_over, LOW);

  while(!pusherClient.connect("7fe26fe9f55d4b78ea02")){
  }
  pusherClient.bind("play_1", playOne);
  pusherClient.bind("play_2", playTwo);
  pusherClient.bind("play_3", playThree);
  pusherClient.bind("play_4", playFour);
  pusherClient.subscribe(receive_channel);
  Serial.println("Pusher Connected Successfully");
} 


void loop() {
  pusherClient.monitor();
  boolean currentState = digitalRead(video_over);
  if(digitalRead(video_over) == HIGH && currentState != previousState) {
    Serial.println("Ready to Play");
    while(!pushMessage(pusher_channel, "ready_to_play", "")){ }
  }
  previousState = currentState; 
}

void playOne(String data){
  Serial.println("Play 1");
  digitalWrite(relayPin1, HIGH);
  delay(500);
  digitalWrite(relayPin1, LOW);
}

void playTwo(String data){
  Serial.println("Play 2");
  digitalWrite(relayPin2, HIGH);
  delay(500);
  digitalWrite(relayPin2, LOW);
}

void playThree(String data){
  Serial.println("Play 3");
  digitalWrite(relayPin3, HIGH);
  delay(500);
  digitalWrite(relayPin3, LOW);
}

void playFour(String data){
  Serial.println("Play 4");
  digitalWrite(relayPin4, HIGH);
  delay(500);
  digitalWrite(relayPin4, LOW);
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


