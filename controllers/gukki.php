<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class gukki extends CI_Controller {

    private $header = 'gukki/header';
    private $footer = 'gukki/footer';

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->load->Model('gukki_model');
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
            $this->load->Model('gukki_model');
            $result = $this->gukki_model->getTableSchema($table);

            echo '<pre>';
            // print_r($result);
            // creating model file
            $data = $this->getModelData($table, $result);
            if (file_put_contents('application/models/' . $table . '_model.php', $data)) {
                echo 'Model written!<br/>';
            } else {
                echo 'Unable to write the Model<br/>';
            }
            // creating controller file
            $data = $this->getController($table, $result);
            if (file_put_contents('application/controllers/' . $table . '.php', $data)) {
                echo 'Controller written!<br/>';
            } else {
                echo 'Unable to write the Controller<br/>';
            }
            if (!file_exists('application/views/' . $table)) {
                mkdir('application/views/' . $table);
            }
            // create view index
            $data = $this->getViewIndex($table, $result);
            if (file_put_contents('application/views/' . $table . '/index.php', $data)) {
                echo 'index Page Created!<br/>';
            } else {
                echo 'Unable to create index page<br/>';
            }
            // create view view
            $data = $this->getViewView($table, $result);
            if (file_put_contents('application/views/' . $table . '/view.php', $data)) {
                echo 'View Created!<br/>';
            } else {
                echo 'Unable to Create View<br/>';
            }
            // create view add
            $data = $this->getViewAdd($table, $result);
            if (file_put_contents('application/views/' . $table . '/add.php', $data)) {
                echo 'Add Created!<br/>';
            } else {
                echo 'Unable to Create Add<br/>';
            }
            // create view edit
            $data = $this->getViewEdit($table, $result);
            if (file_put_contents('application/views/' . $table . '/edit.php', $data)) {
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
        $data = '<?php

class ' . $table . '_model extends CI_Model {

    public $offset = 5;

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function read($limit=0) {

        $query = $this->db->get(\'' . $table . '\', $this->offset, $limit);
        return $query->result();

        return array();
    }

    public function readsingle($id=null) {
        if ($id != null) {
            $query = $this->db->get_where(\'' . $table . '\', array(\'' . $primaryKey . '\' => $id));
            return $query->result();
        }
        return array();
    }

    public function rowCount() {
        $sql = "SELECT COUNT(' . $primaryKey . ') as line FROM ' . $table . '";
        $query = $this->db->query($sql);
        $data = $query->result();
        return $data[0];
    }

    public function add() {
        $data = array(
            ' . $allFieldsInsert . '
        );
        return $this->db->insert(\'' . $table . '\', $data);
    }

    public function update($id) {
        $data = array(
          ' . $allFieldsInsert . '
        );
        $where = "' . $primaryKey . ' = $id";
        $str = $this->db->update_string(\'' . $table . '\', $data, $where);
        return $this->db->query($str);
    }

    public function delete($id) {
        $data = array(\'' . $primaryKey . '\' => $id);
        return $this->db->delete(\'' . $table . '\', $data);
    }

}
?>
';
        return $data;
    }

    private function getController($table, $result) {

        $validationData = '';
        $field = '';
        foreach ($result as $r) {
            if ($r->Key != 'PRI') {
                $field = $r->Field;
                $validationData = $validationData . '$this->form_validation->set_rules(\'' . $r->Field . '\', \'' . $r->Field . '\', \'required\');' . PHP_EOL;
            }
        }
        $data = '<?php

class ' . $table . ' extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(\'' . $table . '_model\');
        $this->load->helper(\'url\');
    }

    public function index($limit=null) {
        $data = array();
        if ($limit == null && $limit < 0) {
            $limit = 0* $this->' . $table . '_model->offset;
        } else {
            $limit = $limit * $this->' . $table . '_model->offset;
        }

        if (isset($_GET[\'m\'])) {
            if ($_GET[\'m\'] == \'0\') {
                $data[\'message\'] = \'Unable to delete\';
            }
            if ($_GET[\'m\'] == \'1\') {
                $data[\'message\'] = \'Deleted Successfully\';
            }
        }
        $total = $this->' . $table . '_model->rowCount();
        $total = $total->line;
        $data[\'total\'] = $total;
        $data[\'offset\'] = $this->' . $table . '_model->offset;
        $records = $this->' . $table . '_model->read($limit);


        $data[\'records\'] = $records;
        $this->load->view(\'' . $table . '/index\', $data);
    }

    public function add($message = null) {
        $data = array();
        if ($message == 1) {
            $data[\'message\'] = \'Save Successfully!\';
        }

        if (isset($_POST[\''.$field.'\'])) {
            $this->load->library(\'form_validation\');
            ' . $validationData . '
            if ($this->form_validation->run() === FALSE) {

            } else {
                if ($this->' . $table . '_model->add()) {
                    $this->load->helper(\'url\');
                    header(\'location:\' . base_url(\'index.php/' . $table . '/add/1\'));
                } else {
                    $data[\'message\'] = \'Got error in saving\';
                }
            }
        }
        $this->load->helper(\'form\');
        $this->load->view(\'' . $table . '/add\', $data);
    }

    public function view($id=null) {
        $data = array();
        if ($id != null) {
            $result = $this->' . $table . '_model->readsingle($id);
            $data[\'result\'] = $result[0];
        }
        $this->load->view(\'' . $table . '/view\', $data);
    }

    public function edit($id = null) {
        if ($id == null) {
            return;
        }
        $data = array();
        if ($id != null && $id >= 0) {
            $result = $this->' . $table . '_model->readsingle($id);
            $data[\'result\'] = $result[0];
        }
        if (isset($_GET[\'m\']) && $_GET[\'m\'] == \'s\') {
            $data[\'message\'] = \'saved succesfully\';
        }
        if (isset($_POST[\''.$field.'\'])) {
            $this->load->library(\'form_validation\');
            ' . $validationData . '
            if ($this->form_validation->run() === FALSE) {

            } else {
                if ($this->' . $table . '_model->update($this->input->post(\'id\'))) {

                    header(\'location:\' . base_url(\'index.php/' . $table . '/edit/\' . $this->input->post(\'id\') . \'?m=s\'));
                } else {
                    $data[\'message\'] = \'Got error in saving\';
                }
            }
        }
        $this->load->helper(\'form\');
        $this->load->view(\'' . $table . '/edit\', $data);
    }

    public function delete($id=null) {
        if ($id != null) {
            if ($this->' . $table . '_model->delete($id)) {
                header(\'location:\' . base_url(\'index.php/' . $table . '/index/?m=1\'));
                die();
            }
        }
        header(\'location:\' . base_url(\'index.php/' . $table . '/index/?m=0\'));
    }

}
?>
';
        return $data;
    }

    public function getViewIndex($table, $results) {
        $this->load->helper('inflector');
        $primaryKey = $this->getPrimaryKey($results);
        $data = '<?php echo isset($message) ? $message : \'\'; ?>
<table width="100%" border="1">
    <tr>
    <th>#</th>
    ';

        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<th>' . humanize($r->Field) . '</th>' . PHP_EOL;
            }
        }
        $data = $data . '<th>Action</th></tr>
    <?php $counter = 0; ?>
    <?php foreach ($records as $record): ?>
        <tr>
            <td><?php echo++$counter; ?></td>';

        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<td><?php echo $record->' . $r->Field . ' ?></td>' . PHP_EOL;
            }
        }
        $data = $data . '<td>
                <a href="<?php echo base_url(\'index.php/' . $table . '/view/\' . $record->' . $primaryKey . '); ?>">View</a>
                <a href="<?php echo base_url(\'index.php/' . $table . '/edit/\' . $record->' . $primaryKey . '); ?>">Edit</a>
                <a href="<?php echo base_url(\'index.php/' . $table . '/delete/\' . $record->' . $primaryKey . '); ?>">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </table>
    <ul>
    <?php for ($i = 0; $i < ($total / $offset); $i++): ?>
            <li><a href="<?php echo base_url(\'index.php/' . $table . '/index/\' . $i); ?>"><?php echo $i ?></a></li>
    <?php endfor; ?>
        </ul>
        <ul>
            <li><a href="<?php echo base_url(\'index.php/' . $table . '/add/\'); ?>">Add</a></li>
            <li><a href="<?php echo base_url(\'index.php/' . $table . '/index/\'); ?>">List</a></li>
</ul>
';
        return $data;
    }

    public function getViewAdd($table, $results) {
        $this->load->helper('inflector');
        $data = '<h4>Add</h4>
<?php echo form_open(\'' . $table . '/add\') ?>
<?php echo validation_errors(); ?>
<?php echo isset($message) ? $message : \'\'; ?>
<table>';
        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<tr>
        <td><label>' . humanize($r->Field) . '</label></td>
        <td><input type="text" name="' . $r->Field . '" value="<?php echo set_value(\'' . $r->Field . '\'); ?>"/></td>
    </tr>' . PHP_EOL;
            }
        }
        $data = $data . '
    <tr>
        <td colspan="2">
            <input type="submit" value="Submit"/>
        </td>
    </tr>
</table>
<?php echo form_close(); ?>
 <ul>
            <li><a href="<?php echo base_url(\'index.php/' . $table . '/add/\'); ?>">Add</a></li>
            <li><a href="<?php echo base_url(\'index.php/' . $table . '/index/\'); ?>">List</a></li>
</ul>
';
        return $data;
    }

    public function getViewView($table, $results) {
        $this->load->helper('inflector');
        $data = '<h4>View</h4>
<table>
    <table border="1" width="100%">';
        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<tr>
            <td><label>' . humanize($r->Field) . '</label></td>
            <td><?php echo $result->' . $r->Field . '?></td>
        </tr>' . PHP_EOL;
            }
        }
        $data = $data . '
       
    </table>
</table>
    <ul>
        <li><a href="<?php echo base_url(\'index.php/' . $table . '/add/\'); ?>">Add</a></li>
        <li><a href="<?php echo base_url(\'index.php/' . $table . '/index/\'); ?>">List</a></li>
</ul>';
        return $data;
    }

    public function getViewEdit($table, $results) {
        $this->load->helper('inflector');
        $primaryKey = $this->getPrimaryKey($results);
        $data = '<h4>Edit</h4>
<?php echo form_open(\'' . $table . '/edit/\' . $result->' . $primaryKey . ') ?>
<?php echo validation_errors(); ?>
<?php echo isset($message) ? $message : \'\'; ?>
<input type="hidden" value="<?php echo $result->' . $primaryKey . ' ?>" name="' . $primaryKey . '"/>
<table>';

        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<tr>
        <td><label>' . humanize($r->Field) . '</label></td>
        <td><input type="text" name="' . $r->Field . '" value="<?php echo $result->' . $r->Field . ' ?>"/></td>
    </tr>';
            }
        }
        $data = $data . '
    <tr>
        <td colspan="2">
            <input type="submit" value="Submit"/>
        </td>
    </tr>
</table>
<?php echo form_close(); ?>
<ul>
        <li><a href="<?php echo base_url(\'index.php/' . $table . '/add/\'); ?>">Add</a></li>
        <li><a href="<?php echo base_url(\'index.php/' . $table . '/index/\'); ?>">List</a></li>
</ul>';
        return $data;
    }

}
?>
