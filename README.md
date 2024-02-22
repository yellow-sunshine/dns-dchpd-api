# dns-dchpd-api
Gathers bind dns zone information and dhcp lease information for the local network

Built using the Slim Framework. The API provides functionality for flushing Bind DNS, retrieving zone details from Bind DNS, and also has functionality to get details and manage Cloudflare DDNS (https://cloudflareddns.com/), and more.

## Endpoints

### Flush Bind DNS
Flush the Bind DNS cache.
/flush-bind-dns

### Zone Details
Retrieve zone details for a specific domain where the local DNS server is the SOA.
/zone-details/example.com

### Cloudflare DDNS
Manage Cloudflare DDNS settings.
/cloudflare-ddns
/cloudflare-ddns/run
/cloudflare-ddns/run/force

### DHCPD
Manage DHCPD settings.
/dhcpd

## Getting Started
To run the API locally, follow these steps:

1. Clone the repository: `git clone https://github.com/your-username/ns1.daha.us-api.git`
2. Install dependencies: `composer install`
3. Run the development server: `composer start`
4. All paths to files are hard coded, you will have to search and update those on each endpoint.
5. You will have to add commands to the sudoers file to allow the webserver permissions

## License
This project is licensed under the [MIT License](LICENSE).
