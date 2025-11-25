## ⚠️ This module is still under active development and may not work - Use at your own risk
## ⚠️ Dokploy is currently still in it's Beta phase, with breaking changes happening all the time - We activatly follow these, but expect this module to break if a new breaking change comes out

# Dokploy Module for WHMCS

WHMCS Module for [Dokploy](https://dokploy.com/)

**This will only work on your own instance of Dokploy**

# Installation

Get the latest version from https://github.com/NodeByteHosting/module-dokploy-whmcs/releases

1. Copy `Dokploy` folder into `modules/servers/` in yout whmcs installation
2. in WHMCS go to System Settings > Products/Services > Servers
3. Add New Server
4. Select Advanced Mode
5. Name your server
6. Fill in hostname with your Dokploy IP or URL
7. Under "Module" select "Dokploy Module"
8. Enter your API Token under "Password" - Do not fill in username or Access Hash these are not used
9. If you are using an URL with SSL make sure "Secure" is ticked. (This is recommended)
10. Done, make your own Product and publish to your clients!