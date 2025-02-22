<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'admin_enqueue_scripts', 'amapress_admin_add_validation_js' );
function amapress_admin_add_validation_js() {
	wp_enqueue_script( 'jquery.validate', plugins_url( '/js/jquery-validate/jquery.validate.min.js', AMAPRESS__PLUGIN_FILE ), array( 'jquery' ) );
	wp_enqueue_script( 'jquery.validate-fr', plugins_url( '/js/jquery-validate/localization/messages_fr.js', AMAPRESS__PLUGIN_FILE ), array( 'jquery.validate' ) );
	wp_enqueue_script( 'jquery.ui.datepicker.validation', plugins_url( '/js/jquery.ui.datepicker.validation.min.js', AMAPRESS__PLUGIN_FILE ), array(
		'jquery.validate',
		'jquery-ui-datepicker'
	) );
}

add_action( 'admin_footer', 'amapress_post_validation' );
function amapress_post_validation() {
	if ( ! is_super_admin() ) {
		if ( isset( $_GET['import_users'] ) ) {
			?>
            <style type="text/css">form#createuser, #create-new-user, #create-new-user ~ p {
                    display: none;
                }

                form#adduser, #add-existing-user, #add-existing-user ~ p {
                    display: block;
                }</style>
			<?php
		} else {
			?>
            <style type="text/css">form#createuser, #create-new-user, #create-new-user ~ p {
                    display: block;
                }

                form#adduser, #add-existing-user, #add-existing-user ~ p {
                    display: none;
                }</style>
			<?php
		}
	}
	?>
    <style type="text/css">
        .amapress-error {
            color: red;
            font-weight: bold;
            display: block;
        }
    </style>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(function () {
            jQuery('.postbox:has(.form-table):not(:has(tr))').css('display', 'none');
            var exclusiveGroupCheckFunction = function (value, element) {
                var $checked = jQuery('input:checkbox:checked', jQuery(element).closest('fieldset'));
                var dataExclusives = [];
                $checked.each(function () {
                    dataExclusives.push(jQuery(this).data('excl'));
                });
                return jQuery.unique(dataExclusives).length <= 1;
            };
            jQuery.validator.addMethod("multicheckReq", function (value, element) {
                return jQuery('input:checkbox:checked,input:radio:checked', jQuery(element).closest('fieldset')).length > 0;
            }, "Sélectionner au moins un élément");
            jQuery.validator.addMethod("exclusiveCheckgroup", exclusiveGroupCheckFunction, "Sélectionner des élements dans un seul groupe");
            jQuery.validator.addMethod("exclusiveContrat", exclusiveGroupCheckFunction, "Attention, vous avez sélectionné des produits/quantités concernant des contrats différents !");
            jQuery.validator.addMethod("tinymcerequired", function (value, element) {
                var content = tinymce.get(element.id).getContent({format: 'text'});
                return jQuery.trim(content) != '';
            }, "Doit être rempli");
            jQuery.validator.addMethod('positiveNumber',
                function (value) {
                    return Number(value) > 0;
                }, 'Doit être supérieur à 0');
            jQuery.validator.setDefaults({
                ignore: ''
            });
            var createBtn = jQuery("form#createuser #createusersub");
            createBtn.hide().after("<input type=\'button\' value=\'Ajouter un utilisateur\' id=\'amapress_add_user\' class=\'amapress_add_user button-primary\' />");
            jQuery("#amapress_add_user").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                }
                catch (ee) {
                }
                if (jQuery('form#createuser').valid()) {
                    createBtn.click();
                } else {
                    alert('Certains champs ne sont pas valides');
                }
            });

            var updateBtn = jQuery("form#your-profile #submit");
            updateBtn.hide().after("<input type=\'button\' value=\'Mettre à jour\' id=\'amapress_update_user\' class=\'amapress_update_user button-primary\' />");
            jQuery("#amapress_update_user, #wp-admin-bar-amapress_update_user_admin_bar button.amapress_update_user").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                }
                catch (ee) {
                }
                if (jQuery('form#your-profile').valid()) {
                    updateBtn.click();
                } else {
                    alert('Certains champs ne sont pas valides');
                }
            });

            var publishBtn = jQuery("form#post #publish");
            publishBtn.hide().after("<input type=\'button\' value=\'Enregistrer\' id=\'amapress_publish\' class=\'amapress_publish button-primary\' />");
            jQuery("#amapress_publish, #wp-admin-bar-amapress_publish_admin_bar button.amapress_publish").click(function () {
                try {
                    window.tinyMCE.triggerSave();
                }
                catch (ee) {
                }
                if (jQuery('form#post').valid()) {
                    publishBtn.click();
                } else {
                    alert('Certains champs ne sont pas valides');
                }
            }).css('display', publishBtn.length > 0 ? 'block' : 'none');

            jQuery.expr[':'].parentHidden = function (a) {
                return jQuery(a).parent().is(':hidden');
            };
            jQuery.validator.addClassRules('emailDoesNotExists', {
                remote: function (element) {
                    return {
                        "url": "<?php echo admin_url( 'admin-ajax.php' ) ?>",
                        "type": "post",
                        "data": {
                            "action": "check_email_exists",
                            "email": function () {
                                return $(element).val();
                            },
                        }
                    }
                }
            });
            jQuery.validator.addClassRules('onlyOneInscription', {
                remote: function (element) {
                    return {
                        "url": "<?php echo admin_url( 'admin-ajax.php' ) ?>",
                        "type": "post",
                        "data": {
                            "action": "check_inscription_unique",
                            "contrats": function () {
                                var contrats = [];
                                jQuery('input.contrat-quantite:checked').each(function () {
                                    contrats.push(jQuery(this).data('excl'));
                                });
                                return contrats.join(',');
                            },
                            "user": function () {
                                return jQuery('#amapress_adhesion_adherent').val();
                            },
                            "related": function () {
                                return jQuery('#amapress_adhesion_related').val();
                            },
                            "post_ID": function () {
                                return jQuery('#post_ID').val();
                            }
                        }
                    }
                }
            });
            var amapress_validator = jQuery('form#post, form#createuser, .titan-framework-panel-wrap form, form form').validate({
                ignore: ":parentHidden",
                onkeyup: false,
                errorPlacement: function (error, element) {
                    error.addClass('amapress-error');
                    if (element.hasClass("exclusiveCheckgroup") || element.hasClass("exclusiveContrat")) {
                        error.insertBefore(element.closest("fieldset"));
                    }
                    else if (element.hasClass("multicheckReq")) {
                        error.insertAfter(element.closest("fieldset"));
                    }
                    else
                        error.insertAfter(element);
                },
                showErrors: function (errorMap, errorList) {
//                    console.log(errorMap);
//                    console.log(errorList);
                    this.defaultShowErrors();
                },
                rules: {
                    "multicheckReq": {
                        "multicheckReq": true,
                    },
                    "exclusiveContrat": {
                        "exclusiveContrat": true,
                    },
                },
            });
        });
        //]]>
    </script>';
	<?php
}
