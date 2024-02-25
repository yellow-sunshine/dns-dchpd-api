<?php
// config.php
define('CONFIG_PATHS', 'paths');

return [
    'paths' => [
        'dhcpLeasesFileLocation' => '/var/lib/dhcp/dhcpd.leases', // Should be an ISC DHCP server lease file fully qualified path to the file
        'dhcpdConfigFileLocation' => '/etc/dhcp/dhcpd.conf', // Should be an ISC DHCP server configuration containing subnets and reservations fully qualified path to the file
    ],
];