#Breakfast Management#
Bachelor project DIKU 2016
Casper Radmer Jensen & Aleksander Adrian Gabel

SETUP:
######## Basic setup                ##########
1. Download XAMPP: https://www.apachefriends.org/download.html
2. Install XAMPP
3. Open the XAMPP Control Panel
4. Change file xampp-to-send-mail-from-localhost
In above file:
Change 'user' and 'password' like the following:
$cfg['Servers'][$i]['user'] = 'root';
$cfg['Servers'][$i]['password'] = '123456';

5. Change phpmyadmin password
Open a shell from within the XAMPP Control panel
and run the following command:
mysqladmin.exe -u root password 123456

6. Create database in phpmyadmin
Click "Admin" next to MySQL in XAMPP Control Panel
and create a new database named "breakfast" (without "")

7. Place 'breakfast' directory from GITHUB in C:/xampp/htdocs/
8. From the XAMPP Control Panel run the modules: Apache AND MySQL
9. Open this adress in Chrome or Explorer: http://localhost/breakfast/views/

######## To make notifications work ##########
10. Change file xampp/php/php.ini
In above file:
a)
Find extension=php_openssl.dll and 
remove the semicolon from the beginning of that line. If it already is removed - fine!
b)
Next find '[mail function]' and replace all existing code here with:
SMTP=smtp.gmail.com
smtp_port=587
sendmail_from=contactbreakfastmanagement@gmail.com
sendmail_path="\"C:\xampp\sendmail\sendmail.exe\" -t"
mail.add_x_header=On

11. Change file xampp/sendmail/sendmail.ini
In above file:
Find '[sendmail]' and replace all existing code here with:
smtp_server=smtp.gmail.com
smtp_port=587
error_logfile=error.log
debug_logfile=debug.log
auth_username=contactbreakfastmanagement@gmail.com
auth_password=breakfast123456
force_sender=contactbreakfastmanagement@gmail.com
smtp_ssl=auto

Or else see guide here: http://stackoverflow.com/questions/15965376/how-to-configure-xampp-to-send-mail-from-localhost


####### Trouble shooting #######
There may be unforseen problems. Like a problem with ports.
Google should be able to fix most problems.
Else watch our demo of the webapp. Link is in our sources in our rapport.