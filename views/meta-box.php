<div class="acf-field">
    <div id="sitmd-sortable-js" class="sitmd-sortable">
        <?php
        if ( $dates ) {
            foreach ( $dates as $date ) {
                ?>
                <div class="sitmd-sortable__item sitmd-sortable-item-js">
                    <div class="acf-input sitmd-sortable__handle sitmd-sortable-handle-js">
                        <input type="datetime-local" class="sitmd-other-dates-js input" value="<?php echo $date; ?>" required>
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
    <div class="sitmd-checkbox">
        <input type="checkbox" name="sitmd_fromto_only" id="sitmd_fromto_only" value="1" <?php echo $sitmd_fromto_only == '1' ? 'checked' : ''; ?>>
        <label for="sitmd_fromto_only"><b>Pouze jako datumy Od / Do</b></label>
    </div>
    <template id="sitmd-sortable-item-tpl-js">
        <div class="sitmd-sortable__item sitmd-sortable-item-js">
            <div class="acf-input sitmd-sortable__handle sitmd-sortable-handle-js">
                <input type="datetime-local" class="sitmd-other-dates-js input" value="">
                <div class="sitmd-remove sitmd-remove-js"><span>Remove</span></div>
            </div>
        </div>
    </template>
    <div style="margin-top: 14px;">
        <label>Pokud chcete zadat jen datum od / do, stačí zadat dva datumy. Musíte ale zaškrtnout políčko.</label>
    </div>
</div>
<input type="hidden" name="sitmd_dates" id="sitmd-other-js" value="<?php echo $dates_string; ?>">
<?php
wp_nonce_field( basename( SITMD_PLUGIN_PATH ), 'sit_special_dates_nonce' );