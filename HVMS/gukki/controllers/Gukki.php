<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Gukki extends CI_Controller {

    private $header = 'gukki/header';
    private $footer = 'gukki/footer';
    private $base_path = 'application/modules/gukki/template/';

    public function __construct() {
        parent::__construct();
        $this->load->Model('gukki_model');
    }

    public function index() {
        $this->load->helper('form');
        $result = $this->gukki_model->listTable();
        $data['result'] = $result;
        $this->load->view($this->header);
        $this->load->view('gukki/index', $data);
        $this->load->view($this->footer);
    }

    public function create() {
        if (isset($_POST['table_name'])) {
            $table = $_POST['table_name'];
            $this->load->helper('file');

            $result = $this->gukki_model->getTableSchema($table);

            echo '<pre>';
            // print_r($result);
            if (!file_exists('application/modules/' . strtolower($table))) {
                echo "Creating directory<br/>";
                mkdir('application/modules/' . strtolower($table));
                mkdir('application/modules/' . strtolower($table) . '/controllers');
                mkdir('application/modules/' . strtolower($table) . '/models');
                mkdir('application/modules/' . strtolower($table) . '/views');
            }
            // creating model file
            $data = $this->getModelData($table, $result);
            if (file_put_contents('application/modules/' . strtolower($table) . '/models/' . ucfirst($table) . '_model.php', $data)) {
                echo 'Model written!<br/>';
            } else {
                echo 'Unable to write the Model<br/>';
            }
            // creating controller file
            $data = $this->getController($table, $result);
            if (file_put_contents('application/modules/' . strtolower($table) . '/controllers/' . ucfirst($table) . '.php', $data)) {
                echo 'Controller written!<br/>';
            } else {
                echo 'Unable to write the Controller<br/>';
            }

            // create view index
            $data = $this->getViewIndex($table, $result);
            if (file_put_contents('application/modules/' . strtolower($table) . '/views/index.php', $data)) {
                echo 'index Page Created!<br/>';
            } else {
                echo 'Unable to create index page<br/>';
            }
            // create view view
            $data = $this->getViewView($table, $result);
            if (file_put_contents('application/modules/' . strtolower($table) . '/views/view.php', $data)) {
                echo 'View Created!<br/>';
            } else {
                echo 'Unable to Create View<br/>';
            }
            // create view add
            $data = $this->getViewAdd($table, $result);
            if (file_put_contents('application/modules/' . strtolower($table) . '/views/create.php', $data)) {
                echo 'Add Created!<br/>';
            } else {
                echo 'Unable to Create Add<br/>';
            }
            // create view edit
            $data = $this->getViewEdit($table, $result);
            if (file_put_contents('application/modules/' . strtolower($table) . '/views/edit.php', $data)) {
                echo 'Edit Page Created!<br/>';
            } else {
                echo 'Unable to Create Edit Page<br/>';
            }
        }
    }

    private function getPrimaryKey($result) {
        foreach ($result as $r) {
            if ($r->Key == 'PRI') {
                return $r->Field;
            }
        }
        return null;
    }

    private function getModelData($table, $result) {
        $primaryKey = $this->getPrimaryKey($result);
        $allFieldsInsert = '';
        foreach ($result as $r) {
            if ($r->Key != 'PRI') {
                $allFieldsInsert = $allFieldsInsert . '\'' . $r->Field . '\' => $this->input->post(\'' . $r->Field . '\'),' . PHP_EOL;
            }
        }
        $this->load->helper('inflector');
        $content = file_get_contents($this->base_path . 'model.template');
        $content = str_replace('[CLASS_NAME]', ucfirst($table), $content);
        $content = str_replace('[TABLE_NAME]', $table, $content);
        $content = str_replace('[PRIMARY_KEY]', $primaryKey, $content);
        $content = str_replace('[ALL_FIELDS]', $allFieldsInsert, $content);
        $content = str_replace('[TABLE_NAME_SINGULAR]', singular($table), $content);
        return $content;
    }

    private function getController($table, $result) {
        $primaryKey = $this->getPrimaryKey($result);

        $validationData = '';
        $field = '';
        $this->load->helper('inflector');
        foreach ($result as $r) {
            if ($r->Key != 'PRI') {
                $field = $r->Field;
                $validationData = $validationData . '$this->form_validation->set_rules(\'' . $r->Field . '\', \'' . humanize($r->Field) . '\', \'required\');' . PHP_EOL;
            }
        }

        $content = file_get_contents($this->base_path . 'controller.template');
        $content = str_replace('[CLASS_NAME]', ucfirst($table), $content);
        $content = str_replace('[TABLE_NAME]', $table, $content);
        $content = str_replace('[PRIMARY_KEY]', $primaryKey, $content);
        $content = str_replace('[VALIDATION_DATA]', $validationData, $content);
        $content = str_replace('[TABLE_NAME_SINGULAR]', singular($table), $content);
        return $content;
    }

    public function getViewIndex($table, $results) {
        $primaryKey = $this->getPrimaryKey($results);
        $this->load->helper('inflector');

        $fields = "";
        $fields_data = "";
        $t = array();
        $d = array();
        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                array_push($t, "'" . humanize($r->Field) . "'");
                array_push($d, '$' . singular($table) . '->' . $r->Field);
            }
        }
        $fields = implode($t, ',');
        $fields_data = implode($d, ',');

        $content = file_get_contents($this->base_path . 'index.template');
        $content = str_replace('[CLASS_NAME]', ucfirst($table), $content);
        $content = str_replace('[TABLE_NAME]', $table, $content);
        $content = str_replace('[HEADER]', $fields, $content);
        $content = str_replace('[FIELDS]', $fields_data, $content);
        $content = str_replace('[PRIMARY_KEY]', $primaryKey, $content);
        $content = str_replace('[TABLE_NAME_SINGULAR]', singular($table), $content);
        return $content;
    }

    public function getViewAdd($table, $results) {
        $primaryKey = $this->getPrimaryKey($results);
        $this->load->helper('inflector');

        $fields = "";
        // iterating over input fields
        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $fields = $fields . '<div class="form-group">
	<?php
		$input = array(
			\'name\' => \'' . $r->Field . '\',
			\'id\' 		=> \'' . $r->Field . '\',
			\'class\' => \'form-control\',
		);
	?>
    <label for="' . $r->Field . '">' . ucfirst(humanize($r->Field)) . '</label>
	<?php echo form_input($input);?>
</div>';
            }
        }
        // iteration end     
        $content = file_get_contents($this->base_path . 'create.template');
        $content = str_replace('[CLASS_NAME]', ucfirst($table), $content);
        $content = str_replace('[TABLE_NAME]', $table, $content);
        $content = str_replace('[FIELDS]', $fields, $content);
        $content = str_replace('[PRIMARY_KEY]', $primaryKey, $content);
        $content = str_replace('[TABLE_NAME_SINGULAR]', singular($table), $content);
        return $content;
    }

    public function getViewView($table, $results) {
        $primaryKey = $this->getPrimaryKey($results);
        $this->load->helper('inflector');
        $fields = "";

        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $fields = $fields . '<tr>
	<th>' . ucfirst(humanize($r->Field)) . '</th>
	<td><?php echo $' . singular($table) . '->' . $r->Field . '?></td>
</tr>';
            }
        }

       
        $content = file_get_contents($this->base_path . 'view.template');
        $content = str_replace('[CLASS_NAME]', ucfirst($table), $content);
        $content = str_replace('[TABLE_NAME]', $table, $content);
        $content = str_replace('[FIELDS]', $fields, $content);
        $content = str_replace('[PRIMARY_KEY]', $primaryKey, $content);
        $content = str_replace('[TABLE_NAME_SINGULAR]', singular($table), $content);
        return $content;
    }

    public function getViewEdit($table, $results) {
        $this->load->helper('inflector');
        $primaryKey = $this->getPrimaryKey($results);
        $fields = "";
        // itteration over input element or table fields
        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $fields = $fields . '<div class="form-group">
	<?php
		$input = array(
			\'name\' => \'' . $r->Field . '\',
			\'id\' 		=> \'' . $r->Field . '\',
			\'class\' => \'form-control\',
			\'value\' => $' . singular($table) . '->' . $r->Field . ',
		);
	?>
    <label for="' . $r->Field . '">' . ucfirst(humanize($r->Field)) . '</label>
	<?php echo form_input($input);?>
</div>';
            }
        }

     
        $content = file_get_contents($this->base_path . 'edit.template');
        $content = str_replace('[CLASS_NAME]', ucfirst($table), $content);
        $content = str_replace('[TABLE_NAME]', $table, $content);
        $content = str_replace('[FIELDS]', $fields, $content);
        $content = str_replace('[PRIMARY_KEY]', $primaryKey, $content);
        $content = str_replace('[TABLE_NAME_SINGULAR]', singular($table), $content);
        return $content;
    }

}
