<!-- General header for keys for contactinfo -->
<div>
 <?php echo _("Your Keys to use for your identity (if empty, the default keys will be applicable)") ?>:
</div>

<!-- currently set keys are choosable when the extra window for writing an email are opend -->
<div>
 <i><?php echo _("Note: Your own certificates as set in the preferences will be available by default. You can add certificates for special identities here.") ?></i>
</div>

<div class="fixed">
 <textarea id="identitykeys" name="identitykeys" rows="4" cols="80" class="fixed"></textarea>
</div>


<!-- adding additional keys for identities, also saved to the database -->
