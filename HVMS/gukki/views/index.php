
<?php echo form_open('gukki/create') ?>
<label>Select Table</label>
<select name="table_name">
    <?php
    foreach ($result as $r) {
        foreach ($r as $key => $value) {
    ?>
    <option value="<?php echo $value?>"><?php echo $value?></option>
    <?php
        }
    }
    ?>
</select>
<input type="submit" value="Generate"/>
<?php echo form_close(); ?>