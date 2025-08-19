#!/usr/bin/ksh
echo "call with parameter: $1<br>"

# need for DBUS (systemctl --user) !
# Die Umgebungsvariable DBUS_SESSION_BUS_ADDRESS gibt die Adresse des D-Bus-Sitzungsbusses an, 
# den Anwendungen verwenden, um miteinander zu kommunizieren. Dieser Bus (ein Middleware-Mechanismus)
# ermöglicht die Interprozesskommunikation auf einem einzelnen System
export DBUS_SESSION_BUS_ADDRESS="unix:path=/run/user/`id -u`/bus"

ps -ef | grep -i hoymiles; echo ""
/usr/bin/systemctl --user ${1:8:20} ahoy.service 2>&1
ps -ef | grep -i hoymiles

