#!/home/pi/ahoyenv/bin/python
"""
26.01.2023 Knut Hallstein - initial
15.04.2025 Knut Hallstein - add "electric meter"

search for missing channel configurations in ahoy.yml
and create new on in VZ
add these new channel configs into ahoy.yml


source ahoyenv/bin/activate
python3 -m pip install ruamel-yaml

"""

import os, json, argparse
from datetime import datetime
from ruamel.yaml import YAML
yaml = YAML(typ='rt')

# check VZ-Installation-Path (tbd)
VZC = "/home/volkszaehler/bin/vzclient"

i_num = 0

def prepare_and_create_channels(channels):
    """ loop over all channels - you can change this """

    # empty dict for results
    creating_results = {}

    for channel in channels:
        if channel["type"] == "temperature":
            creating_results[channel["type"]] = create_vz_channel("temperature",    1, "Temperature")
        if channel["type"] == "yield_total":
            creating_results[channel["type"]] = create_vz_channel('power meter',	1, "Yield-Total")
        if channel["type"] == "yield_today":
            creating_results[channel["type"]] = create_vz_channel('power meter',	1, "Yield-Today")
        if channel["type"] == "ac_power0":
            creating_results[channel["type"]] = create_vz_channel("powersensor",    1, "ac-Power")

        if channel["type"] == "dc_power0":
            creating_results[channel["type"]] = create_vz_channel("powersensor",    1, "dc_Power_0")
        if channel["type"] == "dc_power1":
            creating_results[channel["type"]] = create_vz_channel("powersensor",    1, "dc_Power_1")

        """
        if channel["type"] == "powerfactor":
            creating_results[channel["type"]] = create_vz_channel("valve",          1, "Powerfactor")
        if channel["type"] == "efficiency":
            creating_results[channel["type"]] = create_vz_channel("valve",          1, "Efficiency")

        if channel["type"] == "ac_voltage0":
            creating_results[channel["type"]] = create_vz_channel("voltage",        1, "ac-Voltage")
        if channel["type"] == "ac_current0":
            creating_results[channel["type"]] = create_vz_channel("current",        1, "ac-Current")
        if channel["type"] == "ac_reactive_power0":
            creating_results[channel["type"]] = create_vz_channel("powersensor",    1, "ac-Reactive-Power[Q]")
        if channel["type"] == "ac_frequency0":
            creating_results[channel["type"]] = create_vz_channel("frequency",      1, "ac-Frequency")

        if channel["type"] == "dc_voltage0":
            creating_results[channel["type"]] = create_vz_channel("voltage",        1, "dc_Voltage_0")
        if channel["type"] == "dc_current0":
            creating_results[channel["type"]] = create_vz_channel("current",        1, "dc_Current_0")
        if channel["type"] == "dc_energy_total0":
            creating_results[channel["type"]] = create_vz_channel('power meter',	1, "dc_Yield-Total_0")
        if channel["type"] == "dc_energy_daily0":
            creating_results[channel["type"]] = create_vz_channel('power meter',	1, "dc_Yield_Today_0")
        if channel["type"] == "dc_irradiation0":
            creating_results[channel["type"]] = create_vz_channel("valve",          1, "dc_Irradiation_0")

        if channel["type"] == "dc_voltage1":
            creating_results[channel["type"]] = create_vz_channel("voltage",        1, "dc_Voltage_1")
        if channel["type"] == "dc_current1":
            creating_results[channel["type"]] = create_vz_channel("current",        1, "dc_Current_1")
        if channel["type"] == "dc_energy_total1":
            creating_results[channel["type"]] = create_vz_channel('power meter',	1, "dc_Yield-Total_1")
        if channel["type"] == "dc_energy_daily1":
            creating_results[channel["type"]] = create_vz_channel('power meter',	1, "dc_Yield_Today_1")
        if channel["type"] == "dc_irradiation1":
            creating_results[channel["type"]] = create_vz_channel("valve",          1, "dc_Irradiation_1")
        """

    return creating_results

def extract_channel_uids_from_ahoy_yml(cfg):
    """ read_ahoy_config_and_extract_channel_uid """

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
       # exit()

    return channel_json["channels"]

def create_vz_channel(type, res, ch_vz_name):
    """ creating channel in VZ-DB """

    RCC = f"{VZC} add channel type=\'{type}\' resolution={res} title={ch_vz_name} public=1"
    print(f"{RCC=}")

    # create channel on VZ-DB
    result_data = os.popen(f"{RCC}")
    result_list = [zeile.strip() for zeile in result_data]
    # result is a python-list with one string element only
    # this list-element has preceding char >b'< and attached char >'< 
    # it looks like a bytearray, but it is a string
    # so we have to remove the preceding char >b'< and the attachd char >'<

    if not result_list:
      print("ERROR: 'vzclient' not found - please check path to volkszaehler installation")
      exit(3)

    # check length of result
    if len(result_list) == 0:
       print("ERROR: no result available!")
       exit(2)

    # convert result to string
    result_str = result_list[0].replace("b'","",1).replace("'","",1)

    # convert string to json object
    result_json = json.loads(result_str)

    # test existing key "entity"
    global i_num
    i_num = i_num + 1
    if "entity" in result_json:
        print(f'{i_num:3}: new channel created in VZ-DB')
        print(f'  {result_json["entity"]}')
    else:
        print(f'ERROR: Creating channel not successfull')
        print(result_list)

    return result_json['entity']

def write_new_ahoy_yml(config_file_name, cfg, creating_results):
    """ prepare dict with new channel configprepare_vz_channeluration and write ahoy.yml to disc """

    # update ahoy_config-dict with channel configuration (new UUIDs)
    for channel in creating_results:
        new_channel = {'type' : channel , 'uid' : creating_results[channel]['uuid']}
        if global_config.verbose:
            print(new_channel)
        cfg['ahoy']['volkszaehler']['inverters'][0]['channels'].append(new_channel)

    if global_config.verbose:
      import sys
      yaml.dump(cfg, sys.stdout)

    # rename current ahoy.yml
    target_file_name = config_file_name + datetime.now().strftime("_%Y%m%d_%H%M%S")
    print(f'rename ahoy config file from "{config_file_name}" to "{target_file_name}"')
    os.rename(config_file_name, target_file_name)

    # write new ahoy.yml config file
    print(f'Writing new ahoy config file: {config_file_name}')
    try:
        with open(config_file_name, 'w') as fh_yaml:
            yaml.dump(cfg, fh_yaml)
        fh_yaml.close()
    except FileNotFoundError:
        print("Could not write config file.")
        exit(2)
    return

def even(x, compare_list):
    for element in compare_list:
      if global_config.verbose:
        print(f'check: {x["uid"]} <--> {element["uuid"]}')
      if x["uid"] == element["uuid"]:
        return False
    return True

def compare_ahoy_vz(ahoy_ch, vz_ch):
    exist_ahoy_ch = []
    miss_ahoy_ch = []
    for element in ahoy_ch:
      if even(element, vz_ch):
          miss_ahoy_ch.append(element)
      else:
          exist_ahoy_ch.append(element)
    return (miss_ahoy_ch, exist_ahoy_ch)

if __name__ == '__main__':
    print(f"CREATE necessary channels for AhoyDTU in VZ-DB - START")

    # read command line parameters
    parser = argparse.ArgumentParser(description='CREATE necessary channels for AhoyDTU in VZ-DB', prog="create_ahoy_channels")
    parser.add_argument("-c", "--config-file", nargs="?", required=False, help="configuration file")
    parser.add_argument("-v", "--verbose", action="store_true", default=False, help="Enable detailed debug output")
    global_config = parser.parse_args()

    # detect file name for ahoy.yml
    if isinstance(global_config.config_file, str):
        config_file_name = global_config.config_file     # read config name from CLI-parameter
    else:
        config_file_name = "/home/AhoyDTU/ahoy/ahoy.yml" # if no CLI-parameter given, take standard name

    # Load ahoy.yml config file
    try:
        with open(config_file_name, 'r') as fh_yaml:
            ahoy_cfg = yaml.load(fh_yaml)
        fh_yaml.close()
    except FileNotFoundError:
        print(f'ERROR: Could not find config file: {config_file_name} - exit')
        exit(8)

    # read ahoy configuration and extract channel (uid) configurations
    ahoy_channels = extract_channel_uids_from_ahoy_yml(ahoy_cfg)
    print(f'{len(ahoy_channels)} channels found in ahoy configuration file')
    if global_config.verbose:
        ii = 0
        for element in ahoy_channels:
            ii = ii + 1
            print(f"{ii:3}: {element['type']:20}  uid: {element['uid']}")

    # read uid-channel configurations from ahoy.yml
    vz_channels = retrieve_all_channels_from_database()
    print (f'{len(vz_channels)} Channel configurations "UUIDs" found in VZ-DB')
    if global_config.verbose:
        for element in vz_channels:
            print(f"{element['uuid']}   type:{element['type']:15} {element['title']}")

    # compare and remove not configured UUIDs from ahoy.yml config
    (missing_ahoy_channels, existing_ahoy_channels) = compare_ahoy_vz(ahoy_channels, vz_channels)
    ahoy_cfg['ahoy']['volkszaehler']['inverters'][0]['channels'] = existing_ahoy_channels

    if len(missing_ahoy_channels) == 0:
        print('All ahoy-channels configured successfilly in VZ-DB - nothing to do - exit')
        exit(0)

    print(f'Following {len(missing_ahoy_channels)} ahoy-channel are not configured in VZ-DB')
    ii = 0
    for element in missing_ahoy_channels:
        ii = ii + 1
        print(f"{ii:3}: {element['type']:20}   configured uid: {element['uid']}")

    x = input(f"Do you want to create this {len(missing_ahoy_channels)} channels in VZ-DB? (type 'yes'): ")
    if x != "yes":
      print("Nothing to do - exit")
      exit()

    print("OK - now, we create channels in VZ-DB")

    # prepare and create new channels in VZ-DB
    prepared_vz_channels = prepare_and_create_channels(missing_ahoy_channels)

    # dict "creating_results" contains informations from creating channels in VZ-DB
    write_new_ahoy_yml(config_file_name, ahoy_cfg, prepared_vz_channels)

    print("Fertig")

