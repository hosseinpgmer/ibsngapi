
## Help
This api module works with postgresql database and written with fat free framework (f3), a super lightweight frameworks of php.

**First step**
The first thing you need to do is define your custom api variable in config.ini file. If you do not define this variable, the middle-ware will block communication with this module. you can change it to any api you want, but remember that when you request this api you should send _token variable to any route(URL) of this api.

**Second Step**
The Second thing you need to do is set your database configuration.
you can set this in "helpers/DB.php"\:

    $db = new DB\SQL(
	    'pgsql:host={your_host};port={your_postgresql_port};dbname={your_database_name}',
	    '{your_username}',
	    '{your_password}'
	    );
    return  $db;

Replace **{your_host}** with your **host**.
Replace **{your_postgresql_port}** with your **postgresql port** (default is 5432).
Replace **{your_database_name}** with your **database name**.
Replace **{your_username}** with **database username**.
Replace **{your_password}** with **database password**.

Done. Now you can work with this api module.

## Example

**Add Account (Add User):**
Route:

    http://yourdomain.ir/addAccount

Parameters to send:

    _token: "your_defined_token_in_config_file",
    username: "username",
    password: "password",
    group_name: "group_name"
Other routes and their parameters is in index.php file.
