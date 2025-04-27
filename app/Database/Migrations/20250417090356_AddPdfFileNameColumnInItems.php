<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPdfFileNameColumnInItems extends Migration
{
  public function up()
  {
    $this->db->query('ALTER TABLE ' . $this->db->prefixTable('items') . ' ADD `pdf_filename` VARCHAR(200) DEFAULT NULL AFTER `pic_filename`');
  }

  public function down()
  {
    $this->db->query('ALTER TABLE ' . $this->db->prefixTable('items') . ' DROP COLUMN `pdf_filename`');
  }
}
