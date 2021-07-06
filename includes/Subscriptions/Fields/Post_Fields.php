<?php
/**
 * Post Fields settings field.
 *
 * @since	1.16
 */
namespace Jeero\Subscriptions\Fields;

/**
 * Post Fields class.
 * 
 * @extends Field
 * @since	1.16
 */
class Post_Fields extends Field {
	
	public $post_fields = array();
	
	function get_control_html() {
		ob_start();
				
		?><table id="<?php echo sanitize_title( $this->name ); ?>">
			<thead>
				<tr>
					<th class="name"><?php _e( 'Name', 'jeero' ); ?></th>
					<th class="template"><?php _e( 'Template', 'jeero' ); ?></th>
					<th class="update"><?php _e( 'Update', 'jeero' ); ?></th>
				</tr>
			</thead><?php
				
			foreach( $this->get( 'post_fields') as $post_field ) {
				?><tr>
					<td class="name"><?php
						echo $post_field[ 'title' ];
					?></td>
					<td class="template">
						<textarea name="<?php printf( '%s[%s][template]', $this->name, $post_field['name' ] ); ?>"><?php
							
							if ( 
								!empty( $this->value[ $post_field['name' ] ] ) &&
								!empty( $this->value[ $post_field['name' ] ][ 'template' ] ) 
							) {							
								echo $this->value[ $post_field['name' ] ][ 'template' ]; 
							} else {
								echo $post_field[ 'template' ];
							}
							
						?></textarea>
					</td>
					<td class="update">
						<select name="<?php printf( '%s[%s][update]', $this->name, $post_field['name' ] ); ?>"><?php
							
							$value = 'once';
							if ( 
								!empty( $this->value[ $post_field['name' ] ] ) &&
								!empty( $this->value[ $post_field['name' ] ][ 'update' ] ) 
							) {	
								$value = $this->value[ $post_field['name' ] ][ 'update' ];		
							}

							?><option value="once"<?php selected( 'once', $value, true ); ?>><?php _e( 'on first import', 'jeero' ); ?></option>
							<option value="always"<?php selected( 'always', $value, true ); ?>><?php _e( 'on every import', 'jeero' ); ?></option>
						</select>
					</td>
				</tr><?php
			}
			
		?></table><?php
						
		return ob_get_clean();
	}
	
	/**
	 * Gets the setting value from the post fields form field.
	 * 
	 * @since	1.16
	 * @return	array	The setting value.
	 */
	function get_setting_from_form( ) {
		
		$field_name = $this->name;
		
		if ( empty( $_GET[ $field_name ] ) ) {
			return null;
		}

		$post_fields = array();
		
		$setting = array();
		foreach( $this->post_fields as $post_field ) {
			
			$setting[ $post_field[ 'name' ] ] = array(
				'template' => stripslashes( $_GET[ $field_name ][ $post_field[ 'name' ] ][ 'template' ] ),
				'update' => $_GET[ $field_name ][ $post_field[ 'name' ] ][ 'update' ]
			);
			
		}
				
		return $setting;
		
	}
	
}