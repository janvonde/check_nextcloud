# check_nextcloud

This is a monitoring plugin for [icinga](https://www.icinga.com) to check the status of the [nextcloud](https://nextcloud.com) [security scan](https://scan.nextcloud.com) for a given URL.

![Icingaweb2 screenshot showing the check_nextcloud script](/screenshot.png?raw=true "Icingaweb2 screenshot")


### Usage
Try the plugin at the command line like this:
```
/usr/bin/php ./check_nextcloud.php -H cloud.example.com -u / -z UTC
```

Optional: Define the `LocalPluginDir` constant in `/etc/icinga2/constants.conf` to point to the place where you have placed the `check_nextcloud.php` file. Or you can use the default `PluginDir` already defined in `/etc/icinga2/constants.conf`. See https://icinga.com/docs/icinga-2/latest/doc/05-service-monitoring/#optional-custom-path .

You can define the icinga2 check command as follows:
```
/* Define check command for check_nextcloud */
object CheckCommand "nextcloud" {
  import "plugin-check-command"
  command = [ "/usr/bin/php", LocalPluginDir + "/check_nextcloud.php" ]

  arguments = {
    "-H" = {
      "required" = true
      "value" = "$nc_host$"
    }
    "-u" = {
      "required" = true
      "value" = "$nc_url$"
    }
    "-z" = {
      "required" = true
      "value" = "$nc_timezone$"
    }
  }

  // default values, can be overridden in each host
  vars.nc_url = "/"
  vars.nc_timezone = "UTC"
}
```

For possible timezone values, see PHP docs: https://www.php.net/manual/en/function.date-default-timezone-set.php , https://www.php.net/manual/en/timezones.php .

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

Finally you can use this in a particular host and configure the parameters per-host:
```
object Host ... {
  ...
  vars.nextcloud["mynextcloud"] = {
    nc_host = "cloud.example.com"
    // nc_url = "/" // these are already default, uncomment and customize if necessary
    // nc_timezone = "UTC" // these are already default, uncomment and customize if necessary
  }
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
