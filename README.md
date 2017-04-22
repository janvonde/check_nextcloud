# check_nextcloud

This is a monitoring plugin for [icinga](https://www.icinga.com) to check the status of the [nextcloud](https://nextcloud.com) [security scan](https://scan.nextcloud.com) for a given URL.

![Icingaweb2 screenshot showing the check_nextcloud script](/screenshot.png?raw=true "Icingaweb2 screenshot")


### Usage
Try the plugin at the command line like this:
```
/usr/bin/php ./check_nextcloud.php -H cloud.example.com -u /
```

You can define the icinga2 check command as follows:
```
/* Define check command for check_nextcloud */
object CheckCommand "nextcloud" {
  import "plugin-check-command"
  command = [ LocalPluginDir + "/check_nextcloud.php" ]

  arguments = {
    "-H" = {
      "required" = true
      "value" = "$nc_host$"
    }
    "-u" = {
      "required" = true
      "value" = "$nc_url$"
    }
  }

  vars.nc_url = "/"
}
```

Please don't run this check too often. There is an API limit at the scan.nextcloud.com server at the /api/queue endpoint with arround 250 POST requests a day. I personally run it every 24h:
```
/* Define apply rule for check_nextcloud */
apply Service "nextcloud-" for (instance => config in host.vars.nextcloud) {
  display_name = name
  assign where host.vars.nextcloud
  command_endpoint = host.vars.remote_endpoint
  check_command = "nextcloud"
  vars += config
  max_check_attempts = 3
  check_interval = 24h
  retry_interval = 15m
  enable_notifications = true
}
```


### Changelog
* 2017-03-22: split hostname and url into separate parameters (sumnerboy12)
* 2017-03-18: initial version (janvonde)


### Authors
* [Jan Vonde](https://github.com/janvonde)
* [Ben Jones](https://github.com/sumnerboy12)


### License
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see http://www.gnu.org/licenses/.
