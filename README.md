# op-whmcs-plesk-provisioning-module
###### **Plesk** is the **#1** WebOps hosting platform created to help your customers run, automate and grow their websites, hosting businesses and applications.  Don't miss out on the opportunity to provide your customers with the best tools for their businesses. [Learn how Plesk licenses fit into your product portfolio!](https://openprovider.com/products/plesk-panel/)

# Installation and setup

 1. Copy `./modules/servers/openprovider` into `<WHMCS directory>/modules/servers/openprovider` 

### Email template variables

The below variables can be used to display each product's license number and activation code in email templates
```
{$service_custom_field_licensenumber}
{$service_custom_field_activationcode}
```
 
### Create a product or addon-product

#### Product 

##### Select email template if desired

For the “Welcome Email” field, find the name of the template which includes the license number and activation code variables.

##### Select the “Openprovider Plesk” module and fill in the required details on the "module settings tab":

![img](https://raw.githubusercontent.com/openprovider/op-whmcs-plesk-provisioning-module/images/images/module-settings.png)

- Openprovider username and password for the account where the license will be registered
- License period in months (1, 12, or 24) 
- License-type API code, standard licenses below, check the Openprovider control panel for pricing and additional options.

| License type                                   | API code                      |
| :--------------------------------------------- | :---------------------------- |
| Plesk for VPS Web Admin Edition                | PLESK-12-VPS-WEB-ADMIN-1M     |
| Plesk for VPS Web Host Edition                 | PLESK-12-VPS-WEB-HOST-1M      |
| Plesk for VPS Web Host Edition with CloudLinux | PLESK-12-VPS-WEB-HOST-CLNX-1M |
| Plesk for VPS Web Pro Edition                  | PLESK-12-VPS-WEB-PRO-1M       |
| Plesk for VPS Web Pro Edition with CloudLinux  | PLESK-12-VPS-WEB-PRO-CLNX-1M  |
| Plesk Web Admin Edition                        | PLESK-12-WEB-ADMIN-1M         |
| Plesk Web Host Edition                         | PLESK-12-WEB-HOST-1M          |
| Plesk Web Host Edition with CloudLinux         | PLESK-12-WEB-HOST-CLNX-1M     |
| Plesk Web Pro Edition                          | PLESK-12-WEB-PRO-1M           |
| Plesk Web Pro Edition with CloudLinux          | PLESK-12-WEB-PRO-CLNX-1M      |

##### End user output

In the example, you can see that the license information is displayed below the “domain” tab. 

![img](https://raw.githubusercontent.com/openprovider/op-whmcs-plesk-provisioning-module/images/images/client-area-output.png)

##### Admin area output

In the admin area, we can see the license number and activation code displayed.

![img](https://raw.githubusercontent.com/openprovider/op-whmcs-plesk-provisioning-module/images/images/admin-area-output.png)

 #### Addon product 

- Create an addon product following the above steps, with the addition of the “applicable products” tab, in order to select which products the addon will be displayed
- Select “Show on Order” for this addon to be displayed with the parent products

![img](https://raw.githubusercontent.com/openprovider/op-whmcs-plesk-provisioning-module/images/images/applicable-products.png)

##### End user display when purchasing parent products

- When end user checks out a product which has the addon product attached, they will see available addons displayed at the bottom of the order form:

![img](https://raw.githubusercontent.com/openprovider/op-whmcs-plesk-provisioning-module/images/images/shopping-cart-example.png)

##### End user output after purchase

- End user navigates to the parent product, and clicks on “addons” where they can see the license number and activation code.

![img](https://raw.githubusercontent.com/openprovider/op-whmcs-plesk-provisioning-module/images/images/addon-after-puchase.png)


##### Changing names for the "License Number" and "Activation Code"  fields

The module will create custom fields which you can use in email templates to your end users. If you'd like to change the names of these fields (for example a different translation) You can do the following:

- Edit the `<WHMCS directory>/modules/servers/openprovider/configs.json` file with the desired translations for field names. Default translations are provided
```json
{
  "license_number_name": "License Number",
  "activation_code_name": "Activation Code",
  "license_name_name": "License Name"
}
```
Note that the field “License Name” is ignored in this version. Subsequent versions will use this variable.
