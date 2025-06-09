#!/bin/bash
#####################################################################################
# 21.01.2023 Knut Hallstein - initial
#
# Zum Verbinden des VOLKSZÄHLER mit AHOY müssen:
# - in ahoy.yml:
#   die benötigten AHOY-Messstellen (=Variablen-Namen aus dem AHOY-Logfile)
#   mit einer UUID (aus der VOLKSZÄHLER-Datenbank) verknüpft werden.
# 
# Die AHOY-Messstellen sind als CHANNELs in der VOLKSZÄHLER-Datenbank 
# zu konfigurieren. Die Rückmeldungen dieser Konfigurationsschritte sind UUIDs,
# welche in der ahoy.yml einzutragen ist. Dieser Schritt gilt es zu automatisieren!
# 
# Wir gehen davon aus, dass diese Schritte manuell durchgeführt werden. Da viele 
# Anwender das hierzu notwendige Wissen erst aufbauen müssen, soll dieser Schritt
# mit einem Shell-Script unterstützt werden.
#
# Beim Aufruf des SCRIPT ohne entsprechende Parameter, werden nur die vorhandenen 
# VZ-Channels angezeigt!
#
#####################################################################################
#
echo "Start CREATE all VZ channel for ahoy"

# check VZ-Installation-Path (tbd)
VZC=$HOME/volkszaehler.org/bin/vzclient

create_vz_channel() {
   RCC=$(${VZC} add channel type=$1 resolution=$2 title=$3 public=1)
   # echo $3: ${RCC}
   UUID=$(echo ${RCC} | grep -o '"uuid":"[^"]*' | grep -o '[^"]*$')
   echo "          - type: '${ahoy_type}'"
   echo "            uid:  '${UUID}'"
}

# get ahoy.yml
TYPE_LIST=$(cat ../ahoy.yml | grep -o "\- type: '[^']*" | grep -o "[^']*$")
# echo $TYPE_LIST

# count UUIDs
TYPE_LIST_COUNT=$(echo ${TYPE_LIST} | wc -w)
echo "${TYPE_LIST_COUNT} type configs found"

for ahoy_type in  ${TYPE_LIST}
do
  #echo "check $ahoy_type"
  case ${ahoy_type} in
    ac_voltage0)        create_vz_channel voltage     1       "ac-Voltage" ;;
    ac_current0)        create_vz_channel current     1       "ac-Current" ;;
    ac_power0)          create_vz_channel powersensor 1       "ac-Power" ;;
    ac_reactive_power0) create_vz_channel powersensor 1       "ac-Reactive-Power[Q]" ;;
    ac_frequency0)      create_vz_channel frequency   1       "ac-Frequency" ;;

    dc_voltage0)        create_vz_channel voltage     1       "dc-ch0_Voltage" ;;
    dc_current0)        create_vz_channel current     1       "dc-ch0_Current" ;;
    dc_power0)          create_vz_channel powersensor 1       "dc-ch0_Power" ;;
    dc_energy_total0)   create_vz_channel powersensor 1       "dc-ch0_Yield-Total" ;;
    dc_energy_daily0)   create_vz_channel powersensor 1       "dc-ch0_Yield_Today" ;;
    dc_irradiation0)    create_vz_channel valve       1       "dc-ch0_Irradiation" ;;

    dc_voltage1)        create_vz_channel voltage     1       "dc-ch1_Voltage" ;;
    dc_current1)        create_vz_channel current     1       "dc-ch1_Current" ;;
    dc_power1)          create_vz_channel powersensor 1       "dc-ch1_Power" ;;
    dc_energy_total1)   create_vz_channel powersensor 1       "dc-ch1_Yield-Total" ;;
    dc_energy_daily1)   create_vz_channel powersensor 1       "dc-ch1_Yield_Today" ;;
    dc_irradiation1)    create_vz_channel valve       1       "dc-ch1_Irradiation" ;;

    temperature)        create_vz_channel temperature 1       "Temperature" ;;
    powerfactor)        create_vz_channel valve       1       "Powerfactor" ;;
    yield_total)        create_vz_channel powersensor 1       "Yield-Total" ;;
    yield_today)        create_vz_channel powersensor 1       "Yield-Today" ;;
    efficiency)         create_vz_channel valve       1       "Efficiency" ;;
    *) echo "Dich kenne ich nicht" ;;
  esac
done


