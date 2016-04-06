<?php
namespace PressForward\Core\Admin;
use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Core\Admin\PFTemplater as PFTemplater;
use PressForward\Core\Utility\Forward_Tools as Forward_Tools;
use PressForward\Core\Schema\Nominations as Nominations;
use PressForward\Controllers\Metas;

class Nominated implements HasActions {

        function __construct(Metas $metas, PFTemplater $template_factory, Forward_Tools $forward_tools, Nominations $nominations ) {
            $this->metas = $metas;
            $this->template_factory = $template_factory;
            $this->forward_tools = $forward_tools;
            $this->nomination_slug = $nominations->post_type;
        }

        public function action_hooks() {
            return array(
                array(
                    'hook' => 'feeder_menu',
                    'method' => 'nominate_this_tile',
                    'priority' => 11
                ),
                array(
                    'hook' => 'edit_post',
                    'method' => 'send_nomination_for_publishing'
                ),
                array(
                    'hook' => 'nominations_box',
                    'method' => 'nominations_box_builder'
                ),
                array(
                    'hook' => 'manage_nomination_posts_custom_column',
                    'method' => 'nomination_custom_columns',
                ),
            );
        }

        public function send_nomination_for_publishing() {
            global $post;

            ob_start();
            // verify if this is an auto save routine.
            // If it is our form has not been submitted, so we dont want to do anything
            //if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            if ( isset( $_POST['post_status'] ) && isset( $_POST['post_type'] ) && ( ($_POST['post_status'] == 'publish') || ($_POST['post_status'] == 'draft') ) && ($_POST['post_type'] == $this->nomination_slug )){
            //print_r($_POST); die();
                $item_id = $this->metas->get_post_pf_meta($_POST['ID'], 'origin_item_ID', true);
                pf_log('Sending to last step '.$item_id.' from Nomination post '.$_POST['ID']);
                return $this->forward_tools->nomination_to_last_step($item_id, $_POST['ID']);
            }

        }

    	public function nominate_this_tile(){
    		$this->template_factory->nominate_this('as_feed');
    	}

        //Via http://slides.helenhousandi.com/wcnyc2012.html#15 and http://svn.automattic.com/wordpress/tags/3.4/wp-admin/includes/class-wp-posts-list-table.php
        function nomination_custom_columns ( $column ) {

            global $post;
            switch ($column) {
                case 'nomcount':
                    echo $this->metas->get_post_pf_meta($post->ID, 'nomination_count', true);
                    break;
                case 'nominatedby':
                    $nominatorID = $this->metas->get_post_pf_meta($post->ID, 'submitted_by', true);
                    $user = get_user_by('id', $nominatorID);
                    if ( is_a( $user, 'WP_User' ) ) {
                        echo $user->display_name;
                    }
                    break;
                case 'original_author':
                    $orig_auth = $this->metas->get_post_pf_meta($post->ID, 'authors', true);
                    echo $orig_auth;
                    break;
                case 'date_nominated':
                    $dateNomed = $this->metas->get_post_pf_meta($post->ID, 'date_nominated', true);
                    echo $dateNomed;
                    break;


            }
        }

        public function nominations_box_builder(){
            //wp_nonce_field( 'nominate_meta', 'nominate_meta_nonce' );
            $origin_item_ID = $this->metas->get_post_pf_meta($post->ID, 'origin_item_ID', true);
            $nomination_count = $this->metas->get_post_pf_meta($post->ID, 'nomination_count', true);
            $submitted_by = $this->metas->get_post_pf_meta($post->ID, 'submitted_by', true);
            $source_title = $this->metas->get_post_pf_meta($post->ID, 'source_title', true);
            $posted_date = $this->metas->get_post_pf_meta($post->ID, 'posted_date', true);
            $nom_authors = $this->metas->get_post_pf_meta($post->ID, 'authors', true);
            $item_link = $this->metas->get_post_pf_meta($post->ID, 'item_link', true);
            $date_nominated = $this->metas->get_post_pf_meta($post->ID, 'date_nominated', true);
            $user = get_user_by('id', $submitted_by);
            $item_tags = $this->metas->get_post_pf_meta($post->ID, 'item_tags', true);
            $source_repeat = $this->metas->get_post_pf_meta($post->ID, 'source_repeat', true);
            if (!empty($origin_item_ID)){
                $this->meta_box_printer(__('Item ID', 'pf'), $origin_item_ID);
            }
            if (empty($nomination_count)){$nomination_count = 1;}
            $this->meta_box_printer(__('Nomination Count', 'pf'), $nomination_count);
            if (empty($user)){ $user = wp_get_current_user(); }
            $this->meta_box_printer(__('Submitted By', 'pf'), $user->display_name);
            if (!empty($source_title)){
                $this->meta_box_printer(__('Feed Title', 'pf'), $source_title);
            }
            if (empty($posted_date)){
                $this->meta_box_printer(__('Posted by source on', 'pf'), $posted_date);
            } else {
                $this->meta_box_printer(__('Source Posted', 'pf'), $posted_date);
            }
            $this->meta_box_printer(__('Source Authors', 'pf'), $nom_authors);
            $this->meta_box_printer(__('Source Link', 'pf'), $item_link, true, __('Original Post', 'pf'));
            $this->meta_box_printer(__('Item Tags', 'pf'), $item_tags);
            if (empty($date_nominated)){ $date_nominated = date(DATE_ATOM); }
            $this->meta_box_printer(__('Date Nominated', 'pf'), $date_nominated);
            if (!empty($source_repeat)){
                $this->meta_box_printer(__('Repeated in Feed', 'pf'), $source_repeat);
            }
        }

        public function get_the_source_statement($nom_id){

    		$title_of_item = get_the_title($nom_id);
    		$link_to_item = $this->metas->get_post_pf_meta($nom_id, 'item_link', true);
    		$args = array(
    		  'html_before' => "<p>",
    		  'source_statement' => "Source: ",
    		  'item_url' => $link_to_item,
    		  'link_target' => "_blank",
    		  'item_title' => $title_of_item,
    		  'html_after' => "</p>",
    		  'sourced' => true
    		);
    		$args = apply_filters('pf_source_statement', $args);
    		if (true == $args['sourced']) {
    			$statement = sprintf('%1$s<a href="%2$s" target="%3$s" pf-nom-item-id="%4$s">%5$s</a>',
    				 esc_html($args['source_statement']),
    				 esc_url($args['item_url']),
    				 esc_attr($args['link_target']),
    				 esc_attr($nom_id),
    				 esc_html($args['item_title'])
    			);
    			$statement = $args['html_before'] . $statement . $args['html_after'];
    		} else {
    			$statement = '';
    		}
    		return $statement;

    	}

    	public function get_first_nomination($item_id, $post_type){
    		$q = pf_get_posts_by_id_for_check($post_type, $item_id, true);
    		if ( 0 < $q->post_count ){
    			$nom = $q->posts;
    			$r = $nom[0];
    			return $r;
    		} else {
    			return false;
    		}
    	}

    	public function is_nominated($item_id, $post_type = false, $update = false){
    		if (!$post_type) {
    			$post_type = array('post', 'nomination');
    		}
    		$attempt = $this->get_first_nomination($item_id, $post_type);
    		if (!empty($attempt)){
    			$r = $attempt;
    			pf_log('Existing post at '.$r);
    		} else {
    			$r = false;
    		}
    		/* Restore original Post Data */
    		wp_reset_postdata();
    		return $r;
    	}

    	public function resolve_nomination_state($item_id){
    		$pt = array('nomination');
    		if ($this->is_nominated($item_id, $pt)){
    			$attempt = $this->get_first_nomination($item_id, $pt);
    			if (!empty($attempt)){
    				$nomination_id = $attempt;
    				$nominators = $this->metas->retrieve_meta($nomination_id, 'nominator_array');
    				if (empty($nominators)){
    					pf_log('There is no one left who nominated this item.');
    					pf_log('This nomination has been taken back. We will now remove the item.');
    					pf_delete_item_tree( $nomination_id );
    				} else {
    					pf_log('Though one user retracted their nomination, there are still others who have nominated this item.');
    				}
    			} else {
    				pf_log('We could not find the nomination to resolve the state of.');
    			}
    		} else {
    			pf_log('There is no nomination to resolve the state of.');
    		}
    	}

    	public function change_nomination_count($id, $up = true){
    		$nom_count = $this->metas->retrieve_meta($id, 'nomination_count');
    		if ( $up ) {
    			$nom_count++;
    		} else {
    			$nom_count--;
    		}
    		$check = $this->metas->update_pf_meta($id, 'nomination_count', $nom_count);
    		pf_log('Nomination now has a nomination count of ' . $nom_count . ' applied to post_meta with the result of '.$check);
    		return $check;
    	}

    	public function toggle_nominator_array($id, $update = true){
    		$nominators = $this->metas->retrieve_meta($id, 'nominator_array');
    		$current_user = wp_get_current_user();
    		$user_id = $current_user->ID;
    		if ($update){
    			$nominators[] = $user_id;
    		} else {
    			if(($key = array_search($user_id, $nominators)) !== false) {
    				unset($nominators[$key]);
    			}
    		}
    		$check = $this->metas->update_pf_meta($id, 'nominator_array', $nominators);
    		return $check;
    	}

    	public function did_user_nominate($id, $user_id = false){
    		$nominators = $this->metas->retrieve_meta($id, 'nominator_array');
    		if (!$user_id){
    			$current_user = wp_get_current_user();
    			$user_id = $current_user->ID;
    		}
    		if (!empty($nominators) && in_array($user_id, $nominators)){
    			return true;
    		} else {
    			return false;
    		}
    	}

    	public function handle_post_nomination_status($item_id, $force = false){
    		$nomination_state = $this->is_nominated($item_id);
    		$check = false;
    		if (false != $nomination_state){
    			if ( $this->did_user_nominate($nomination_state) ){
    				$this->change_nomination_count($nomination_state, false);
    				$this->toggle_nominator_array($nomination_state, false);
    				$check = false;
    				pf_log( 'user_unnonminated' );
    				$this->resolve_nomination_state($item_id);
    			} else {
    				$this->change_nomination_count($nomination_state);
    				$this->toggle_nominator_array($nomination_state);
    				$check = false;
    				pf_log('user_added_additional_nomination');
    			}
    		} else {
    			$check = true;
    		}
    		pf_log($check);
    		return $check;
    	}

        public function remove_post_nomination($date, $item_id, $post_type, $updateCount = true){
    		$postsAfter = pf_get_posts_by_id_for_check( $post_type, $item_id );
    		//Assume that it will not find anything.
    		$check = false;
    		if ( $postsAfter->have_posts() ) : while ( $postsAfter->have_posts() ) : $postsAfter->the_post();

    					$id = get_the_ID();
    					$origin_item_id = $this->metas->retrieve_meta($id, 'origin_item_ID');
    					$current_user = wp_get_current_user();
    					if ($origin_item_id == $item_id) {
    						$check = true;
    						$nomCount = $this->metas->retrieve_meta($id, 'nomination_count');
    						$nomCount--;
    						$this->metas->update_pf_meta($id, 'nomination_count', $nomCount);
    						if ( 0 != $current_user->ID ) {
    							$nominators_orig = $this->metas->retrieve_meta($id, 'nominator_array');
    							if (true == in_array($current_user->ID, $nominators_orig)){
    								$nominators_new = array_diff($nominators_orig, array($current_user->ID));
    								if (empty($nominators_new)){
    									wp_delete_post( $id );
    								} else {
    									$this->metas->update_pf_meta( $id, 'nominator_array', $nominators_new );
    								}
    							}
    						}
    					}
    		endwhile;	else :
    			pf_log(' No nominations found for ' . $item_id);
    		endif;
    		wp_reset_postdata();
    	}

        public function get_post_nomination_status($date, $item_id, $post_type, $updateCount = true){
            global $post;
            //Get the query object, limiting by date, type and metavalue ID.
            pf_log('Get posts matching '.$item_id);
            $postsAfter = pf_get_posts_by_id_for_check( $post_type, $item_id );
            //Assume that it will not find anything.
            $check = false;
            pf_log('Check for nominated posts.');
            if ( $postsAfter->have_posts() ) : while ( $postsAfter->have_posts() ) : $postsAfter->the_post();

                    $id = get_the_ID();
                    pf_log('Deal with nominated post '.$id);
                    $origin_item_id = $this->metas->retrieve_meta($id, 'origin_item_ID');
                    $current_user = wp_get_current_user();
                    if ($origin_item_id == $item_id) {
                        $check = true;
                        //Only update the nomination count on request.
                        if ($updateCount){
                            if ( 0 == $current_user->ID ) {
                                //Not logged in.
                                //If we ever reveal this to non users and want to count nominations by all, here is where it will go.
                                pf_log('Can not find user for updating nomionation count.');
                                $nomCount = $this->metas->retrieve_meta($id, 'nomination_count');
                                $nomCount++;
                                $this->metas->update_pf_meta($id, 'nomination_count', $nomCount);
                                                            $check = 'no_user';
                            } else {
                                $nominators_orig = $this->metas->retrieve_meta($id, 'nominator_array');
                                if (!in_array($current_user->ID, $nominators_orig)){
                                    $nominators = $nominators_orig;
                                    $nominator = $current_user->ID;
                                                                    $nominators[] = $current_user->ID;
                                    $this->metas->update_pf_meta($id, 'nominator_array', $nominator);
                                    $nomCount = $this->metas->get_post_pf_meta($id, 'nomination_count', true);
                                    pf_log('So far we have a nominating count of '.$nomCount);
                                                                    $nomCount++;
                                                                    pf_log('Now we have a nominating count of '.	$nomCount);
                                    $check_meta = $this->metas->update_pf_meta($id, 'nomination_count', $nomCount);
                                                                    pf_log('Attempt to update the meta for nomination_count resulted in: ');
                                                                    pf_log($check_meta);
                                                                    $check = true;
                                } else {
                                    $check = 'user_nominated_already';
                                }
                            }


                        return $check;
                        break;
                        }
                    } else {
                        pf_log('No nominations found for ' . $item_id);
                        $check = 'unmatched_post';
                    }
            endwhile;	else :
                pf_log(' No nominations found for ' . $item_id);
                $check = 'unmatched_post';
            endif;
            wp_reset_postdata();
            return $check;
        }

        /**
         * Handles an archive action submitted via AJAX
         *
         * @since 1.7
         */
        public static function archive_a_nom(){
            $pf_drafted_nonce = $_POST['pf_drafted_nonce'];
            if (! wp_verify_nonce($pf_drafted_nonce, 'drafter')){
                die($this->__('Nonce not recieved. Are you sure you should be archiving?', 'pf'));
            } else {
                $current_user = wp_get_current_user();
                $current_user_id = $current_user->ID;
                add_post_meta($_POST['nom_id'], 'archived_by_user_status', 'archived_' . $current_user_id);
                print_r(__('Archived.', 'pf'));
                # @TODO This should have a real AJAX response.
                die();
            }
        }


        public function meta_box_printer($title, $variable, $link = false, $anchor_text = 'Link'){
            echo '<strong>' . $title . '</strong>: ';
            if (empty($variable)){
                echo '<br /><input type="text" name="'.$title.'">';
            } else {
                if ($link === true){
                    if ($anchor_text === 'Link'){
                        $anchor_text = $this->__('Link', 'pf');
                    }
                    echo '<a href=';
                    echo $variable;
                    echo '" target="_blank">';
                    echo $anchor_text;
                    echo '</a>';
                } else {
                    echo $variable;
                }
            }

            echo '<br />';
        }

        function build_nomination() {

            // Verify nonce
            if ( !wp_verify_nonce($_POST[PF_SLUG . '_nomination_nonce'], 'nomination') )
                die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'pf' ) );

            if ('' != (get_option('timezone_string'))){
                date_default_timezone_set(get_option('timezone_string'));
            }
            //ref http://wordpress.stackexchange.com/questions/8569/wp-insert-post-php-function-and-custom-fields, http://wpseek.com/wp_insert_post/
            $time = current_time('mysql', $gmt = 0);
            //@todo Play with post_exists (wp-admin/includes/post.php ln 493) to make sure that submissions have not already been submitted in some other method.
                //Perhaps with some sort of "Are you sure you don't mean this... reddit style thing?
                //Should also figure out if I can create a version that triggers on nomination publishing to send to main posts.


            //There is some serious delay here while it goes through the database. We need some sort of loading bar.
            ob_start();
            $current_user = wp_get_current_user();
            $userID = $current_user->ID;
            //set up nomination check
            $item_wp_date = $_POST['item_wp_date'];
            $item_id = $_POST['item_id'];
            //die($item_wp_date);
            pf_log('We handle the item into a nomination?');

                if ( !empty( $_POST['pf_amplify'] ) && ( '1' == $_POST['pf_amplify'] ) ){
                    $amplify = true;
                } else {
                    $amplify = false;
                }
                $nomination_id = $this->forward_tools->item_to_nomination( $item_id, $_POST['item_post_id'] );
                if ( is_wp_error($nomination_id) || !$nomination_id ){
                    pf_log('Nomination has gone wrong somehow.');
                    pf_log($nomination_id);
                    $response = array(
                        'what' => 'nomination',
                        'action' => 'build_nomination',
                        'id' => $_POST['item_post_id'],
                        'data' => 'Nomination failed',
                        'supplemental' => array(
                            'originID' => $item_id,
                            'nominater' => $userID,
                            'buffered' => ob_get_flush()
                        )
                    );
                } else {
                //$this->metas->transition_post_meta( $_POST['item_post_id'], $newNomID, $amplify );
                    $response = array(
                        'what' => 'nomination',
                        'action' => 'build_nomination',
                        'id' => $nomination_id,
                        'data' => $nomination_id . ' nominated.',
                        'supplemental' => array(
                            'originID' => $item_id,
                            'nominater' => $userID,
                            'buffered' => ob_get_flush()
                        )
                    );

                }
                    $xmlResponse = new WP_Ajax_Response($response);
                    $xmlResponse->send();
                ob_end_flush();
                die();
        }

        function user_nomination_meta($increase = true){
            $current_user = wp_get_current_user();
            $userID = $current_user->ID;
            if (get_user_meta( $userID, 'nom_count', true )){

                            $nom_counter = get_user_meta( $userID, 'nom_count', true );
                            if ($increase) {
                                $nom_counter++;
                            }	else {
                                $nom_counter--;
                            }
                            update_user_meta( $userID, 'nom_count', $nom_counter, true );

            } elseif ($increase) {
                            add_user_meta( $userID, 'nom_count', 1, true );

            } else {
                return false;
            }
        }

        public function simple_nom_to_draft($id = false){
            global $post;
            ob_start();
            $pf_drafted_nonce = $_POST['pf_nomination_nonce'];
            if (! wp_verify_nonce($pf_drafted_nonce, 'nomination')){
                die(__('Nonce not recieved. Are you sure you should be drafting?', 'pf'));
            } else {
                if (!$id){
                    $id = $_POST['nom_id'];
                    //$nom = get_post($id);
                    $item_id = $this->metas->retrieve_meta($id, 'item_id');
                }
                $item_id = $this->metas->retrieve_meta($id, 'item_id');
                $last_step_id = $this->forward_tools->nomination_to_last_step( $item_id, $id );
        ##Check
                    add_post_meta($id, 'nom_id', $id, true);
                    //$this->metas->transition_post_meta($id, $new_post_id, true);
                    $already_has_thumb = has_post_thumbnail($id);
                    if ($already_has_thumb)  {
                        $post_thumbnail_id = get_post_thumbnail_id( $id );
                        set_post_thumbnail($last_step_id, $post_thumbnail_id);
                    }

                    $response = array(
                        'what' => 'draft',
                        'action' => 'simple_nom_to_draft',
                        'id' => $last_step_id,
                        'data' => $last_step_id  . ' drafted.',
                        'supplemental' => array(
                            'originID' => $id,
                            'buffered' => ob_get_flush()
                        )
                    );

                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
                ob_end_flush();
                die();
            }
        }

        function build_nom_draft() {
            global $post;
            // verify if this is an auto save routine.
            // If it is our form has not been submitted, so we dont want to do anything
            //if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            $pf_drafted_nonce = $_POST['pf_drafted_nonce'];
            if (! wp_verify_nonce($pf_drafted_nonce, 'drafter')){
                die(__('Nonce not recieved. Are you sure you should be drafting?', 'pf'));
            } else {
        ##Check
            # print_r(__('Sending to Draft.', 'pf'));
        ##Check
            //print_r($_POST);
            ob_start();

            $item_id = $_POST['item_id'];
                $nomination_id = $this->forward_tools->nomination_to_last_step( $item_id, $_POST['nom_id'] );
                $response = array(
                    'what' => 'draft',
                    'action' => 'build_nom_draft',
                    'id' => $nomination_id,
                    'data' => $nomination_id . ' drafted.',
                    'supplemental' => array(
                        'originID' => $item_id,
                        'buffered' => ob_get_flush()
                    )
                );
                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
                ob_end_flush();
                die();
            }
        }


}