<?php if ($this->heading): ?>
<option value=""><?php echo $this->heading ?></option>
<?php endif; ?>

<?php if ($this->customhtml): ?>
<?php echo $this->customhtml ?>
<?php endif; ?>

<?php if ($this->new_mbox): ?>
<option value="" disabled="disabled">- - - - - - - -</option>
<option class="flistCreate" value=""><?php echo _("Create Mailbox") ?></option>
<option value="" disabled="disabled">- - - - - - - -</option>
<?php endif; ?>

<?php if ($this->optgroup): ?>
<optgroup class="flistMailboxes" label="<?php echo _("Mailboxes") ?>">
<?php endif; ?>

<?php echo $this->tree ?>

<?php if ($this->optgroup): ?>
</optgroup>
<?php endif; ?>

<?php if ($this->vfolder): ?>
<?php if ($this->optgroup): ?>
<optgroup class="flistVfolders" label="<?php echo _("Virtual Folders") ?>">
<?php else: ?>
<option value="" disabled="disabled">- - - - - - - -</option>
<?php endif; ?>

<?php foreach ($this->vfolder as $v): ?>
<?php echo $this->optionTag($v['v'], $v['l'], $v['sel']) ?>
<?php endforeach; ?>

<?php if ($this->optgroup): ?>
</optgroup>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($this->tasklist)): ?>
<?php if ($this->optgroup): ?>
<optgroup class="flistTasklists" label="<?php echo _("Task Lists") ?>">
<?php else: ?>
<option value="" disabled="disabled"></option>
<option value="" disabled="disabled">- - <?php echo _("Task Lists") ?> - -</option>
<?php endif; ?>

<?php foreach ($this->tasklist as $v): ?>
<?php echo $this->optionTag($v['v'], $v['l']) ?>
<?php endforeach; ?>

<?php if ($this->optgroup): ?>
</optgroup>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($this->notepad)): ?>
<?php if ($this->optgroup): ?>
<optgroup class="flistNotepads" label="<?php echo _("Notepads") ?>">
<?php else: ?>
<option value="" disabled="disabled"></option>
<option value="" disabled="disabled">- - <?php echo _("Notepads") ?> - -</option>
<?php endif; ?>

<?php foreach ($this->notepad as $v): ?>
<?php echo $this->optionTag($v['v'], $v['l']) ?>
<?php endforeach; ?>

<?php if ($this->optgroup): ?>
</optgroup>
<?php endif; ?>
<?php endif; ?>
