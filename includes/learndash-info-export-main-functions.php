<?php
// this function get the courses ids 
function learndash_get_all_course_ids()
{
    $query_args = array(
        'post_type'         =>   'sfwd-courses',
        'post_status'       =>   'publish',
    );
    $query = new WP_Query($query_args);
    if ($query instanceof WP_Query) {
        return $query->posts;
    }
}
//this function is made to export csv
function export_learndash()
{
    $leardashCourses = learndash_get_all_course_ids(); //put courses into variable
    //menu html déroulant
    echo '<label for="course-select">Choisir une Formation:</label><form action="" method="post"><select name="coursesSelect" id="coursesSelect"><option value="">--Formations--</option>';
    //interieur du menu deroulant
    foreach ($leardashCourses as $post) {

        echo '<option value="' . $post->ID . '" >' . $post->post_title . '</option>';
    }
    //bouton action du menu deroulant
    echo '<input style="margin-left:5px;" class="button action" type="submit" name="submit" value="Appliquer"></form>';
    //si "cree csv" est cliqué
    if (isset($_POST['submit'])) {
        //vérifier qu'il n'est pas vide
        if (!empty($_POST['coursesSelect'])) {
            //recuperer l id cours selectionné
            $courseId = $_POST['coursesSelect'];
            //recuperer le nom du cous
            $courseName = get_post($courseId);
            $courseName = $courseName->post_title;
            // ou l'on veut mettre le fichier.csv
            $path = wp_upload_dir();
            // nom du fichier
            $outstream = fopen($path['path'] . "/" . wp_date('d-m-Y') . '_' . $courseName . ".csv", "w");
            //trouver les groupes lié a ce cours
            $groups_ids = learndash_get_course_groups($courseId);
            //concatener les utilisateurs des groupes dans un seul tableau
            $usersFromAllGroupes = array(); //créer un tableau
            foreach ($groups_ids as $group_id) {
                $userFromThisGroup = learndash_get_groups_user_ids($group_id);
                $usersFromAllGroupes = array_merge($usersFromAllGroupes, $userFromThisGroup);
            }
            $usersFromAllGroupes = array_unique($usersFromAllGroupes);
            $fields = array(ID, Formation, email, Prénom, Nom, Adresse, "Date 1ere étape", "Date Dernière étape", "Étapes completées", "Dates et Produits Achatés"); //créer la premiere ligne du fichier csv
            fputcsv($outstream, $fields); //ajouter cette premiere ligne au fichier
            foreach ($usersFromAllGroupes as $user_ID) { //pour chaque utilisateur
                if (!user_has_role($user_ID, 'ritesdefemmes') && !user_has_role($user_ID, 'administrator')) { //si ne fait pas parti de l'equipe alors
                    //remise a zero des variables
                    $titresName = '';
                    $countItem = 0;
                    //retrouver toutes les commandes d'un client
                    $customer_orders = get_posts(array(
                        'numberposts' => -1,
                        'meta_key' => '_customer_user',
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'meta_value' => $user_ID,
                        'post_type' => wc_get_order_types(),
                        'post_status' => array_keys(wc_get_order_statuses()),
                    ));
                    $Order_Array = array();
                    //traiter chaque commande
                    foreach ($customer_orders as $customer_order) {
                        $orderq = wc_get_order($customer_order);
                        $Order_Array[] = [
                            "ID" => $orderq->get_id(),
                            "Value" => $orderq->get_total(),
                            "Date" => $orderq->get_date_created()->date_i18n('Y-m-d'),
                            "items" => $orderq->get_items(),
                        ];
                        foreach ($orderq->get_items() as $item_key => $item) {
                            if ($countItem > 0) {
                                $titresName =  $titresName . "\r";
                            }
                            $titresName =  $titresName . $orderq->get_date_created()->date_i18n('d-m-Y') . ": " . $item->get_name();
                            $countItem = +1;
                        }
                    }
                    $fields = array();
                    $userinfo = $courseName;
                    array_push($fields, $user_ID);
                    array_push($fields, $userinfo);
                    $user = get_user_by('id', $user_ID);
                    $userinfo = $user->user_email;
                    array_push($fields, $userinfo);
                    $userinfo = $user->first_name;
                    array_push($fields, $userinfo);
                    $userinfo = $user->last_name;
                    array_push($fields, $userinfo);
                    $userinfo = $user->billing_address_1 . ", " . $user->billing_postcode . ", " . $user->billing_city;
                    array_push($fields, $userinfo);
                    // $userinfo = wp_date('d/m/Y',learndash_user_group_enrolled_to_course_from( $user_ID, $courseId));
                    $dateInscription = wp_date('d/m/Y', learndash_activity_course_get_earliest_started($user_ID,  $courseId,));
                    if ($dateInscription != "01/01/1970") {
                        array_push($fields, $dateInscription);
                    } else {
                        array_push($fields, "Jamais Connecté");
                    }
                    last_user_activity($user_ID, $courseId, $fields);
                    $coursProgress = learndash_user_get_course_progress($user_ID, $courseId, 'summary');
                    $avancement = $coursProgress['completed'] . "/" . $coursProgress['total'] . " étapes";
                    array_push($fields, $avancement);
                    array_push($fields, $titresName);
                    fputcsv($outstream, $fields);  //output the user info line to the csv file
                }
            }
            fclose($file);
            fclose($outstream);
            echo '<a style="margin:5px;" class="button wc-action-button wc-action-button-download_csv download_csv" href="' . $path['url'] . '/' . wp_date('d-m-Y') . '_' . $courseName . '.csv">Télécharger ' . $courseName . '.csv </a>';  //make a link to the file so the user can download.
            $file = fopen($path['path'] . "/" . wp_date('d-m-Y') . '_' . $courseName . ".csv", "r");
            echo '<div class="wrap" style="margin-right: 20px;"><table class=" widefat striped "style="white-space:pre-wrap"><tbody>';
            $countTable = 0;
            $countTable2 = 0;
            while (($line = fgetcsv($file)) !== FALSE) {
                echo '<tr>';
                //$line is an array of the csv elements
                foreach ($line as $lin) {
                    if ($countTable == 0) {
                        if ($countTable2 == 0) {
                            echo '<thead>';
                        }
                        echo '<th class="manage-column" style="vertical-align: middle;" >';
                        echo $lin;
                        echo '</th>';
                        if ($countTable2 == 9) {
                            echo '</thead>';
                        }
                        $countTable2 = $countTable2 + 1;
                    } else {
                        if ($lin == "Jamais Connecté") {
                            echo '<td class="manage-column" style="vertical-align: middle;" >';
                            echo "<p style='color:red';>Jamais Connecté</p>";
                            echo '</td>';
                        } else {
                            echo '<td class="manage-column" style="vertical-align: middle;" >';
                            echo $lin;
                            echo '</td>';
                        }
                    }
                }
                echo '</tr>';
                $countTable = +1;
            }
            echo '</tbody></table></div>';
        }
        //s'il est vide
        else {
            echo 'Merci de choisir une Formation';
        }
    }
}

/*
  Display a custom menu page
 */
function my_custom_menu_page_mail()
{
    echo '<div id="wpbody-content"><div class="wrap"> <h1 class="wp-heading-inline">Exporter les informations des utilisateurs</h1><br><p>les membres de l\'équipe ne seront pas inclus</div></div>';
    echo export_learndash();
}
/*
  Last Activity push
 */
function last_user_activity($user_ID,  $courseId, &$fields)
{
    $lastActivity = learndash_activity_course_get_latest_completed_step($user_ID,  $courseId,)[activity_completed];
    if ($lastActivity) {
        $lastActivitydate = wp_date('d-m-Y', $lastActivity);
        array_push($fields, $lastActivitydate);
    } else {
        $lastActivitydate = " ";
        array_push($fields, $lastActivitydate);
    }
}
/*
Vérifier le role d'un utilisateur
*/
function user_has_role($user_ID, $role_name)
{
    $user_meta = get_userdata($user_ID);
    if (in_array($role_name, (array) $user_meta->roles)) {
        return true;
    }
    return false;
}
//parametrage extention wordpress
function custom_menu_page_info_export()
{
    $user = wp_get_current_user();
    add_menu_page(
        __('Custom Menu Title', 'textdomain'),
        'Export Formation',
        'ritesdefemmes', //capacité demandée pour afficher le menu
        'exportUtilisateurFormations',
        'my_custom_menu_page_mail',
        'dashicons-media-spreadsheet',
        48
    );
}
add_action('admin_menu', 'custom_menu_page_info_export');