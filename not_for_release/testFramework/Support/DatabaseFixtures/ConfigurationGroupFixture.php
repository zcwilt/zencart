<?php

namespace Tests\Support\DatabaseFixtures;

class ConfigurationGroupFixture extends DatabaseFixture implements FixtureContract
{
    public function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS  configuration_group (
          configuration_group_id int(11) NOT NULL auto_increment,
          configuration_group_title varchar(64) NOT NULL default '',
          configuration_group_description varchar(255) NOT NULL default '',
          sort_order int(5) default NULL,
          visible int(1) default '1',
          PRIMARY KEY  (configuration_group_id),
          KEY idx_visible_zen (visible)
        ) ENGINE=MyISAM;";

        $this->connection->query($sql);

        $sql = "CREATE TABLE configuration (
  configuration_id int(11) NOT NULL auto_increment,
  configuration_title text NOT NULL,
  configuration_key varchar(180) NOT NULL default '',
  configuration_value text NOT NULL,
  configuration_description text NOT NULL,
  configuration_group_id int(11) NOT NULL default '0',
  sort_order int(5) default NULL,
  last_modified datetime default NULL,
  date_added datetime NOT NULL default '0001-01-01 00:00:00',
  use_function text default NULL,
  set_function text default NULL,
  val_function text default NULL,
  PRIMARY KEY  (configuration_id),
  UNIQUE KEY unq_config_key_zen (configuration_key),
  KEY idx_key_value_zen (configuration_key,configuration_value(10)),
  KEY idx_cfg_grp_id_zen (configuration_group_id)
) ENGINE=MyISAM;
";

        $this->connection->query($sql);

    }

    public function seeder()
    {
        $sql = "INSERT INTO configuration_group (configuration_group_title, configuration_group_description, sort_order, visible) values('test-group-title', 'test-group-description', 1, 1)";
        $this->connection->query($sql);
    }
}
