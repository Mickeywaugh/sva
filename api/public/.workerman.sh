#!/bin/bash

# Auth:Mickeywaugh@qq.com
# WorkermanBundle with symfony7.

[ -n "$1" ] && OP=$1
[ -z "$1" ] && OP="debug"

case "$OP" in
conn)
  echo "Showing connections ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php connections

  ;;
debug)
  echo "Debug ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php start -d | tail -f ../var/log/log.log
  ;;
startd)
  echo "Starting WorkermanBundle with deamon mode ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php start -d &

  ;;
start)
  echo "Starting WorkermanBundle ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php start

  ;;
restart)
  echo "Restarting WorkermanBundle ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php restart
  sleep 1
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php status
  ;;
reload)
  echo "Reloading WorkermanBundle ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php reload

  ;;
status)
  echo "Status of WorkermanBundle ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php status

  ;;
stop)
  echo "Stopping WorkermanBundle ..."
  APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php ./index.php stop

  ;;
*)
  echo $"Usage: sh workerman.sh {conn|debug|start|startd|restart|reload|status|stop}"
  exit 1
  ;;
esac
