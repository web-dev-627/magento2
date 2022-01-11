Command Line Installation

1. Backup your web directory and store database



2. Download the Cappacity  installation package



3. Upload the contents of the Cappacity installation package to your store root directory



4. In the SSH console of your server, navigate to your store root folder:

cd path_to_the_store_root_folder
run the following command:


php bin/magento setup:upgrade

after:

php bin/magento setup:di:compile

after:

php bin/magento setup:static-content:deploy


5. Flush the store cache; log out from the backend and log in again