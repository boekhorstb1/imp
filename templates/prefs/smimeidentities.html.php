
<div class="prefsContainer item"> 
 
 <div> 
  Your identities: 
 </div> 
  
 <div> 
 <select id="default_identity" name="default_identity"> 
 <?php foreach ($this->identitieslist as $key => $value): ?>
  
   <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
  
   <?php endforeach ?>

  </select> 
 </div> 
  