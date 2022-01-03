## CONTACTS
* Email: dongvantruong1117@gmail.com  

## INSTALLATION

Upload files to the server
Extract a magetrend_extension-name-x.x.x.zip archive
Login to web server via FTP client and go to your magento directory.
Create a directory: app/code/Magetrend/ModuleName
ModuleName has to be replaced to actual module name of extension. You can find a module name in registration.php file which is in .zip package.
As per this example, module name would be NewsletterPopup and the files should be uploaded to app/code/Magetrend/NewsletterPopup
Upload all extracted files to extension directory.

### ENABLE EXTENSION
* enable extension (use Magento 2 command line interface \*):
>`$> php bin/magento module:enable Magetrend_PdfTemplates`

Login to a server via ssh client
Go to Magento home directory

Run the following ssh commands:

php -f bin/magento setup:upgrade;

php -f bin/magento setup:di:compile;

php -f bin/magento setup:static-content:deploy;


Enjoy!

Best regards,

Truong Dong
