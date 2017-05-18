To install this plugin, you have to :
1. download into your %SF_PLUGIN_DIR% directory
2. add its name into the config/extra-plugins.php file
3. add its needed modules into the apps/*/config/extra-modules.php as it is proposed in the plugin config/ dir
4. add "exceptionCatcherFilter: on" in apps/*/config/filters.yml
4. build the model, filters and forms, and create the database
5. activate the modules in your targetted sf1 app as proposed in the config/extra-modules.php.template provided by the plugin
6. discover new submenus in the "ticketing" menu and various modules
