import sys
import sounddevice as sd
import queue
import json
from vosk import Model, KaldiRecognizer

q = queue.Queue()
def callback(indata, frames, time, status):
    q.put(bytes(indata))

model = Model("vosk-model-small-en-us-0.15")
rec = KaldiRecognizer(model, 16000)

with sd.RawInputStream(samplerate=16000, blocksize = 8000, dtype='int16',
                       channels=1, callback=callback):
    print("Speak into the microphone...")
    while True:
        data = q.get()
        if rec.AcceptWaveform(data):
            print(json.loads(rec.Result())["text"])
        else:
            print(json.loads(rec.PartialResult())["partial"])
