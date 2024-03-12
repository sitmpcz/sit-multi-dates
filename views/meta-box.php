<div class="acf-field">
	<div class="acf-label">
        <label for="sitmd_from"><b>Datum od (nebo den konání)</b></label>
	</div>
	<div class="acf-input">
		<input type="date" pattern="\d{4}-\d{2}-\d{2}" name="sitmd_from" id="sitmd_from" class="input" value="<?php echo $sitmd_from; ?>" required>
	</div>
</div>
<div class="acf-field">
    <div class="acf-label">
        <label for="sitmd_to"><b>Datum do</b></label>
    </div>
    <div class="acf-input">
        <input type="date" pattern="\d{4}-\d{2}-\d{2}" name="sitmd_to" id="sitmd_to" class="input" value="<?php echo $sitmd_to; ?>" required>
    </div>
</div>
<div class="acf-field">
    <div class="acf-label">
        <label><b>Další datumy (mezi od - do)</b></label>
    </div>
    <div id="sitmd-sortable-js" class="sitmd-sortable">
        <?php
        if ( $sitmd_other_dates ) {
            foreach ( $sitmd_other_dates as $date ) {
        ?>
            <div class="sitmd-sortable__item sitmd-sortable-item-js">
                <div class="acf-input sitmd-sortable__handle sitmd-sortable-handle-js">
                    <input type="date" class="sitmd-other-dates-js input" value="<?php echo $date; ?>" required>
                    <div class="sitmd-remove sitmd-remove-js"><span>Remove</span></div>
                </div>
            </div>
        <?php
            }
        }
        ?>
    </div>
    <div class="sitmd-add-wrapper sitmd-sortable-filter-js">
        <div id="sitmd-add-js" class="sitmd-add"><span>Add</span></div>
    </div>
    <template id="sitmd-sortable-item-tpl-js">
        <div class="sitmd-sortable__item sitmd-sortable-item-js">
            <div class="acf-input sitmd-sortable__handle sitmd-sortable-handle-js">
                <input type="date" class="sitmd-other-dates-js input" value="">
                <div class="sitmd-remove sitmd-remove-js"><span>Remove</span></div>
            </div>
        </div>
    </template>
</div>
<input type="hidden" name="sitmd_other" id="sitmd-other-js" value="<?php echo $sitmd_other; ?>">
<?php
wp_nonce_field( basename( SITMD_PLUGIN_PATH ), 'sit_special_dates_nonce' );