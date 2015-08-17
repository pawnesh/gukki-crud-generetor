<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Gukki extends CI_Controller {

    private $header = 'gukki/header';
    private $footer = 'gukki/footer';

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
            // creating model file
            $data = $this->getModelData($table, $result);
            if (file_put_contents('application/models/' . ucfirst($table) . '_model.php', $data)) {
                echo 'Model written!<br/>';
            } else {
                echo 'Unable to write the Model<br/>';
            }
            // creating controller file
            $data = $this->getController($table, $result);
            if (file_put_contents('application/controllers/' . ucfirst($table) . '.php', $data)) {
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
            if (file_put_contents('application/views/' . $table . '/create.php', $data)) {
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
        $this->load->helper('inflector');
        $data = '<?php
if ( ! defined(\'BASEPATH\')) exit(\'No direct script access allowed\');

class ' . ucfirst($table) . '_model extends CI_Model{
	 public function __construct(){
		$this->load->database();
	 }
	 
	 public function get_' . singular($table) . '($' . $primaryKey . ' = NULL,$page = 0, $limit = 10){
		if($' . $primaryKey . ' == NULL){
			// return all users
			$from = $limit*$page;
			$query = $this->db->get(\'' . $table . '\',$limit,$from);
			return $query->result();
		}
		// return ' . singular($table) . ' with ' . $primaryKey . '
		$query = $this->db->get_where(\'' . $table . '\',array(\'' . $primaryKey . '\' => $' . $primaryKey . '));
		return $query->first_row();
	 }
	 
	 public function get_row_count(){
		return $this->db->count_all(\'' . $table . '\');
	 }
	
	 
	public function set_' . singular($table) . '($' . $primaryKey . ' = NULL){
		$data = array(
			' . $allFieldsInsert . '
		);
		if($' . $primaryKey . ' == NULL){
			// need to create entery
			return $this->db->insert(\'' . $table . '\', $data);
		}
		// need to update entery
		$this->db->where(\'' . $primaryKey . '\',$' . $primaryKey . ');
		return $this->db->update(\'' . $table . '\', $data);
	}
	
	public function remove_' . singular($table) . '($' . $primaryKey . '){
		$this->db->where(\'' . $primaryKey . '\',$' . $primaryKey . ');
		$this->db->delete(\'' . $table . '\');
	}
	 
}';
        return $data;
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


        $data = '<?php
if ( ! defined(\'BASEPATH\')) exit(\'No direct script access allowed\');

class '.ucfirst($table).' extends CI_Controller{
	
	public function __construct(){
		parent::__construct();
		$this->load->model(\''.$table.'_model\');
		$this->load->helper(\'url\');
	}
	
	public function index($page = 0){
		$this->load->library(\'table\');
		$data[\'total_rows\'] = $this->'.$table.'_model->get_row_count();
		$data[\'per_page\'] = 5;
		$data[\'current_page\'] = $page;
	
		$data[\'title\'] = \''.ucfirst($table).'\';
		$data[\''.$table.'\'] = $this->'.$table.'_model->get_'.singular($table).'(NULL,$page,$data[\'per_page\']);

		$this->load->view(\'common/header\', $data);
        $this->load->view(\''.$table.'/index\', $data);
        $this->load->view(\'common/footer\');
	}
	
	public function create($status = 0){
		if($status == 1){
			$data[\'message\'] = \''.ucfirst(singular($table)).' created\';
		}
		$this->load->helper(\'form\');
		$this->load->library(\'form_validation\');
		$data[\'title\'] = \'Create '.ucfirst(singular($table)).'\';
	
		'.$validationData.'
		
		if ($this->form_validation->run() === FALSE){	
			
		}else{
			$this->'.$table.'_model->set_'.singular($table).'();
			redirect(\'/'.$table.'/create/1\');
		}
		
		$this->load->view(\'common/header\', $data);
		$this->load->view(\''.$table.'/create\',$data);
		$this->load->view(\'common/footer\');
	}
	
	public function view($'.$primaryKey.' = NULL){
		if($'.$primaryKey.' == NULL){
			show_404();
		}
		$data[\'title\'] = \''.ucfirst(singular($table)).' View\';
		$data[\''.singular($table).'\'] = $this->'.$table.'_model->get_'.singular($table).'($'.$primaryKey.');
		if(empty($data[\''.singular($table).'\'])){
			show_404();
		}
		
		$this->load->view(\'common/header\', $data);
		$this->load->view(\''.$table.'/view\',$data);
		$this->load->view(\'common/footer\');
	}
		
	public function edit($'.$primaryKey.'= NULL,$status = NULL){
		if($status == 1){
			$data[\'message\'] = \''.ucfirst(singular($table)).' updated\';
		}
		if($'.$primaryKey.' == NULL){
			show_404();
		}
		$data[\''.singular($table).'\'] = $this->'.$table.'_model->get_'.singular($table).'($'.$primaryKey.');
		if(empty($data[\''.singular($table).'\'])){
			show_404();
		}
		$this->load->helper(\'form\');
		$this->load->library(\'form_validation\');
		$data[\'title\'] = \'Modify '.ucfirst(singular($table)) . '\';
	
		' . $validationData . '
		
		if ($this->form_validation->run() === FALSE){	
			
		}else{
			$this->' . $table . '_model->set_' . singular($table) . '($' . $primaryKey . ');
			redirect(\'/' . $table . '/edit/\'.$' . $primaryKey . '.\'/1\');
		}
		$this->load->view(\'common/header\', $data);
		$this->load->view(\'' . $table . '/edit\',$data);
		$this->load->view(\'common/footer\');
	}
	
	
	public function remove($' . $primaryKey . ' = NULL){
		if($' . $primaryKey . '== NULL || !is_numeric($' . $primaryKey . ')){
			show_404();
		}
		
		$this->load->library(\'user_agent\');
		$url =  $this->agent->referrer();
		$this->' . $table . '_model->remove_' . singular($table) . '($' . $primaryKey . ');
		// return to referrer url if not from other site.
		if (!$this->agent->is_referral() && !empty($url)){
			redirect($url);
		}else{
			redirect(\'' . $table . '/\');
		}
	}
}';
        return $data;
    }

    public function getViewIndex($table, $results) {
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

        $data = '<ul class="pager">
	<li><a href="<?php echo site_url(\''.$table.'/\')?>">List</a></li>
	<li><a href="<?php echo site_url(\''.$table.'/create\')?>">Add</a></li>
</ul>
<?php
	$template = array(
        \'table_open\'            => \'<table class="table table-stripped table-bordered">\',

        \'thead_open\'            => \'<thead>\',
        \'thead_close\'           => \'</thead>\',

        \'heading_row_start\'     => \'<tr>\',
        \'heading_row_end\'       => \'</tr>\',
        \'heading_cell_start\'    => \'<th>\',
        \'heading_cell_end\'      => \'</th>\',

        \'tbody_open\'            => \'<tbody>\',
        \'tbody_close\'           => \'</tbody>\',

        \'row_start\'             => \'<tr>\',
        \'row_end\'               => \'</tr>\',
        \'cell_start\'            => \'<td>\',
        \'cell_end\'              => \'</td>\',

        \'row_alt_start\'         => \'<tr>\',
        \'row_alt_end\'           => \'</tr>\',
        \'cell_alt_start\'        => \'<td>\',
        \'cell_alt_end\'          => \'</td>\',

        \'table_close\'           => \'</table>\'
);

	$this->table->set_template($template);

	$this->table->set_heading('.$fields.');
	foreach($'.$table.' as $'.singular($table).'){
		$links = anchor(\''.$table.'/view/\'.$'.singular($table).'->id,\'view\',array(\'title\'=>\'View '.ucfirst(singular($table)).'\',\'class\'=>\'btn btn-sm btn-success\'));
		$links .= \' | \'.anchor(\''.$table.'/edit/\'.$'.singular($table).'->id,\'edit\',array(\'title\'=>\'Edit '.ucfirst(singular($table)).'\',\'class\'=>\'btn btn-sm btn-warning\'));
		$links .= \' | \'.anchor(\''.$table.'/remove/\'.$'.singular($table).'->id,\'remove\',array(\'title\'=>\'remove '.ucfirst(singular($table)).'\',\'class\'=>\'btn btn-sm btn-danger\'));
		
		$this->table->add_row('.$fields_data.',$links);
		
	}
?>
<?php echo $this->table->generate();?>
<ul class="pagination">
<?php
	for($i=0;$i<$total_rows/$per_page;$i++){
		?>
		<li <?php echo ($i == $current_page)?\'class="active" \':\'\'?>><a href="<?php echo site_url(\'' . $table . '/index/\'.$i)?>"><?php echo $i ?></a></li>
		<?php
	}
?>
</ul>';
        return $data;
    }

    public function getViewAdd($table, $results) {
        $this->load->helper('inflector');

        $data = '<ul class="pager">
	<li><a href="<?php echo site_url(\''.$table.'/\')?>">List</a></li>
	<li><a href="<?php echo site_url(\''.$table.'/create\')?>">Add</a></li>
</ul>
<?php 
	if(isset($message) && !empty($message)){
		?>
		<div class="alert alert-info"><?php echo $message ?></div>
		<?php
	}
?>
<?php 
	$validation_error = validation_errors(); 
	if(!empty($validation_error)){
		?>
		<div class="alert alert-danger"><?php echo $validation_error ?></div>
		<?php
	}
?>
<?php echo form_open(\'' . $table . '/create\') ?>';
        // iterating over input fields
        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<div class="form-group">
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
        
        $data = $data . '
<?php echo form_submit(\'submit\', \'Save!\',\'class="btn btn-primary" \');?>
<?php echo form_close();?>';
        return $data;
    }

    public function getViewView($table, $results) {
        $this->load->helper('inflector');
        $data = '<ul class="pager">
	<li><a href="<?php echo site_url(\'' . $table . '/\')?>">List</a></li>
	<li><a href="<?php echo site_url(\'' . $table . '/create\')?>">Add</a></li>
</ul>
<table class="table table-bordered">';

        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<tr>
	<th>' . ucfirst(humanize($r->Field)) . '</th>
	<td><?php echo $' . singular($table) . '->' . $r->Field . '?></td>
</tr>';
            }
        }

        $data = $data.'

</table>';
        return $data;
    }

    public function getViewEdit($table, $results) {
        $this->load->helper('inflector');
        $primaryKey = $this->getPrimaryKey($results);
        $data = '<ul class="pager">
	<li><a href="<?php echo site_url(\'' . $table . '/\')?>">List</a></li>
	<li><a href="<?php echo site_url(\'' . $table . '/create\')?>">Add</a></li>
</ul>
<?php 
	if(isset($message) && !empty($message)){
		?>
		<div class="alert alert-info"><?php echo $message ?></div>
		<?php
	}
?>
<?php 
	$validation_error = validation_errors(); 
	if(!empty($validation_error)){
		?>
		<div class="alert alert-danger"><?php echo $validation_error ?></div>
		<?php
	}
?>
<?php echo form_open(\'' . $table . '/edit/\'.$' . singular($table) . '->id) ?>';
        // itteration over input element or table fields
        foreach ($results as $r) {
            if ($r->Key != 'PRI') {
                $data = $data . '<div class="form-group">
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

        $data = $data . '
<?php echo form_submit(\'submit\', \'Save!\',\'class="btn btn-primary" \');?>
<?php echo form_close();?>';
        return $data;
    }

}

