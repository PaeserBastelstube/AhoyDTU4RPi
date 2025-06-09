#!/usr/bin/env python3
"""
21.01.2023 Knut Hallstein - initial

Beim einfachen Aufruf des SCRIPT werden die nicht mehr benötigten 
VZ-Channels ermittelte und angezeigt, dabei passiert noch nichts. 

Nach dem Auflisten der nicht mehr benötigten CHANNELs erfolgt eine Abfrage, 
ob sie wirklich gelöscht werden sollen. 
Wenn Ja, bitte die 3 Buchstaben "yes" eintippen und mit ENTER bestätigen.
"""

import os, json, argparse, yaml
from yaml.loader import SafeLoader
from datetime import datetime

# check VZ-Installation-Path (tbd)
VZC = f"{os.environ['HOME']}/volkszaehler.org/bin/vzclient"

def extract_channels_from_ahoy_config(file_name):
    """ extract_channels_from_ahoy_config """

    # Load ahoy.yml config file
    try:
        with open(file_name, 'r') as fh_yaml:
            cfg = yaml.load(fh_yaml, Loader=SafeLoader)
        fh_yaml.close()
    except FileNotFoundError:
        print(f'Could not find config file: {config_file_name}')
        exit(2)
    except yaml.YAMLError as e_yaml:
        print('Failed to load configuration from {config_file_name}: {e_yaml}')
        exit(1)

    # read AHOY configuration file into dict
    config = dict(cfg.get('ahoy', {}))

    if 'volkszaehler' not in config:
       print("'volkszaehler' not in ahoy.yml configuration")
       exit(6)

    if 'inverters' not in config['volkszaehler']:
       print("'inverters' not in ahoy.yml configuration in section 'volkszaehler'")
       exit(5)

    if 'channels' not in config['volkszaehler']['inverters'][0]:
       print("'channels' not in ahoy.yml configuration in section volkszaehler[inverters]")
       exit(4)

    return config['volkszaehler']['inverters'][0]['channels']

def retrieve_all_channels_from_database():
    """ read channels from VZ-DB """

    # retrieve all channels from VZ-DB
    channel_data = os.popen(f"{VZC} get channel")
    channel_list = [zeile.strip() for zeile in channel_data]
    # channel result is a python-list with one string element only
    # this string has preceding char >b'< and attached char >'< 
    # it looks like a bytearray, but it is a string
    # so we have to remove the preceding char >b'< and the attachd char >'<

    if not channel_list:
      print("ERROR: 'vzclient' not found - please check path to volkszaehler installation")
      exit(7)

    #convert channel-result to string
    channel_str = channel_list[0].replace("b'","",1).replace("'","",1)
    # print(channel_str)

    # convert string to json object
    channel_json = json.loads(channel_str)

    # test existing key "channels"
    if "channels" not in channel_json:
       print(f"No channels found in VZ-DB - exit")
       exit(3)

    UUID_count = len(channel_json["channels"])
    if UUID_count == 0:
       print(f"No channels found in VZ-DB - exit")
       exit()

    return channel_json["channels"]

def even(x, compare_list):
    for element in compare_list:
      if global_config.verbose:
        print(f'check: {x["uuid"]} <--> {element["uid"]}')
      if x["uuid"] == element["uid"]:
        return False
    return True

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='CREATE necessary channels for ahoy in VZ-DB', prog="create_ahoy_channels")
    parser.add_argument("-c", "--config-file", nargs="?", required=False, help="configuration file")
    parser.add_argument("-v", "--verbose", action="store_true", default=False, help="Enable detailed debug output")
    global_config = parser.parse_args()

    print(f"DELETE not necessary needed channels in VZ-DB - START")

    # read all channel configurations (UUIDs) from VZ-DB
    vz_channels = retrieve_all_channels_from_database()
    print (f'{len(vz_channels)} Channel configurations "UUIDs" found in VZ-DB')

    if global_config.verbose:
      for element in vz_channels:
        print(f"{element['uuid']}   type:{element['type']:15} {element['title']}")


    # detect file name for ahoy.yml
    if isinstance(global_config.config_file, str):
      config_file_name = global_config.config_file
    else:
      config_file_name = "ahoy.yml"

    # read uid-channel configurations from ahoy.yml
    ahoy_uuids = extract_channels_from_ahoy_config(config_file_name)
    print(f'{len(ahoy_uuids)} Channel configurations found in ahoy.yml file')

    if global_config.verbose:
      for element in ahoy_uuids:
        print(f"{element['uid']}   type:{element['type']:15}")


    # compare
    vz_channels = [ vz_channel for vz_channel in vz_channels if even(vz_channel, ahoy_uuids) ]

    if len(vz_channels) == 0:
      print('All VZ-Channels configured successfilly in ahoy.aml - nothing to do - exit')
      exit(0) 

    print('Following VZ-channel configurations (UUIDs) are not configured in ahoy.yml')
    for element in vz_channels:
      print(f"{element['uuid']}   type:{element['type']:15} {element['title']}")

    x = input(f"Do you want to delete {len(vz_channels)} not necessary channel configurations? (type 'yes'): ")
    if x != "yes":
      print("Nothing to do - exit")
      exit()

    print("OK - we delete channels in VZ-DB, now")

    for vz_channel in vz_channels:
      delete_result = os.popen(f"{VZC} -u {vz_channel['uuid']} delete channel")
      del_res_list = [zeile.strip() for zeile in delete_result]
      print(f'delete channel "{vz_channel["uuid"]}" --> {del_res_list}')

    print("thanks for your trust - exit")

