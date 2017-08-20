from grovepi import *
import time
import json
import requests
import sys

# digital pins
ultrasonic_pin = 2
second_ultrasonic_pin = 6
humtemp_pin = 3
button_pin = 4
led_pin = 5
buzzer_pin = 7

# analog read
light_pin = 0
sound_pin = 1
rotary_pin = 2

pinMode(ultrasonic_pin, 'INPUT')
pinMode(second_ultrasonic_pin, 'INPUT')
pinMode(humtemp_pin, 'INPUT')
pinMode(button_pin, 'INPUT')
pinMode(led_pin, 'OUTPUT')
pinMode(buzzer_pin, 'OUTPUT')

# sending init
#hostname = 'http://192.168.1.101:3000/server_grove.php'
hostname = 'http://nw1pdey0gpbi.azurewebsites.net/grove/server_grove.php'
access_token = 'U0tOSTMyNwo='
response = None


def send(post_data):
    json_data = json.dumps(post_data)
    response = requests.post(hostname, data=json_data)
    print response.ok
    return response.ok


timeout_max = 2 * 300 * 4  # 20 min
timeout = timeout_max - 1

time.sleep(0.1)
print 'System initialised'
while True:
    try:
        #time.sleep(2 * 10 * 60)
        time.sleep(0.5)
        post_data = {'access_token': access_token, 'dist': -1}

        # distance
        try:
            distance = ultrasonicRead(ultrasonic_pin)
            post_data['dist'] = distance
        except TypeError:
            print 'Distance error'

        # second ultrasonic
        try:
            second_distance = ultrasonicRead(second_ultrasonic_pin)
            post_data['dist2'] = second_distance
        except TypeError:
            print 'Distance error'

        # light
        light_level = analogRead(light_pin)
        light_perc = (light_level / 751.0) * 100
        post_data['light'] = light_perc

        # sound
        sound_level = analogRead(sound_pin)
        sound_perc = (sound_level / 1023.0) * 100
        post_data['sound'] = sound_perc

        # dht HAS TO BE at the bottom. good software
        [temp, humidity] = dht(humtemp_pin, 0)
        post_data['temp'] = temp
        post_data['hum'] = humidity

        if (
                (post_data['dist'] < 100 or post_data['dist'] > 150)
                and
                (post_data['dist2'] < 100 or post_data['dist2'] > 150)
        ):
            timeout = timeout_max
        else:
            timeout += 1

        if timeout == timeout_max:
            timeout = 0
            if not send(post_data):
                time.sleep(0.5)
                send_data(post_data)  # retry

    except KeyboardInterrupt:
        break

    except IOError as e:
        print 'IOError', e
