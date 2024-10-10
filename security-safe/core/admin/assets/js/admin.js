
jQuery( function($){

    // Cleanup Form Before Submit
    $(".security-safe .wrap, .security-safe-premium .wrap").on( "click", "input[type=submit]", function(e) {

        $("select option[value='-1']:selected").parent("select").attr('disabled', 'disabled');

    });


    // Import File Click
    $(".security-safe #export-import, .security-safe-premium #export-import").on( "click", ".file-select", function(e) {

        $('.security-safe #export-import #import-file, .security-safe-premium #export-import #import-file').trigger('click');

    });

    // Import File Change
    $(".security-safe #export-import, .security-safe-premium #export-import").on( "change", "#import-file", function(e) {

        let filename = e.target.files[0].name;
        $(this).siblings('.file-selected').html(filename);

    });

    // Access Settings
    $('.wp-security-safe_page_security-safe-user-access, .wp-security-safe_page_security-safe-premium-user-access').on('click', 'input[name="block_usernames"]', secsafeBlockUsernames);

    function secsafeBlockUsernames(){

        let block_usernames = $('input[name="block_usernames"]');
        let block_usernames_list = $('textarea[name="block_usernames_list"]');

        if ( block_usernames.is(':checked') ) {
            // Enable the textarea box for blocked usernames
            block_usernames_list.attr('disabled', false);
        } else {
            // disable the textarea box for blocked usernames
            block_usernames_list.attr('disabled', true);
        }
    };

    // initially run
    secsafeBlockUsernames();

    /**
     * Restores the text area to the default value in settings
     */
    $('.security-safe, .security-safe-premium').on('click', '.reset-textarea', function(){
        let linkClicked = $(this);
        let row = linkClicked.closest('tr');
        let textarea = row.find('textarea:first-of-type');
        let textareaDefaultValue = $(linkClicked.attr('href')).val();

        textarea.val(textareaDefaultValue);
    });


});
