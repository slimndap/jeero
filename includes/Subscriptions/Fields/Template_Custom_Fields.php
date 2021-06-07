<?php
/**
 * Textarea settings field.
 * @since	1.10
 */
namespace Jeero\Subscriptions\Fields;

class Template_Custom_Fields extends Field {
	

	function get_row_html( $index, $name='', $template='' ) {
		
		ob_start();
		
		?><tr>
			<td class="name">
				<input type="text" class="regular-text" name="<?php echo $this->name; ?>[<?php echo $index; ?>][name]" value="<?php echo esc_attr( $name ); ?>">
			</td>
			<td class="template">
				<textarea name="<?php echo $this->name; ?>[<?php echo $index; ?>][template]" class="large-text code"><?php
					echo esc_html( $template ); 
				?></textarea>
			</td>
			<td class="actions">
				<button type="button" class="button" onclick="jQuery( this ).parent().parent().remove();"><?php
					_e( 'Delete' );
				?></button>
			</td>
		</tr><?php
			
		return ob_get_clean();
		
	}
	
	function get_control_html() {
		ob_start();
				
		?><table id="<?php echo sanitize_title( $this->name ); ?>">
			<thead>
				<tr>
					<th class="name">Name</th>
					<th class="template">Template</th>
					<th class="actions"></th>
				</tr>
			</thead><?php
		?></table>
		<p>
			<button class="add-custom-field button" type="button"><?php 
				_e( 'Add custom field', 'jeero' ); 
			?></button>
		</p><?php
			
		if ( !empty( $this->get_value() ) ) {
			
			?><script>
				
				jeero_templates.custom_fields[ '<?php echo sanitize_title( $this->name ); ?>' ] = <?php echo json_encode( $this->get_value() ); ?>;
				
			</script><?php
		}
			
		return ob_get_clean();
	}
	
	function get_setting_from_form( ) {
		
		$field_name = sanitize_title( $this->name );
		
		if ( empty( $_GET[ $field_name ] ) ) {
			return null;
		}
		
		$custom_fields = array();
		
		foreach( $_GET[ $field_name ] as $custom_field ) {
			
			if ( empty( $custom_field[ 'template' ] ) ) {
				continue;
			}
			
			$custom_fields[] = $custom_field;
		}
		
		return $custom_fields;
		
	}
	
	function get_value() {
		
		$value = parent::get_value();
		
		$custom_fields = array();

		if ( empty( $value ) ) {
			return $custom_fields;
		}
				
		foreach ( $value as $custom_field ) {
			$custom_fields[] = array(
				'name' => $custom_field[ 'name' ],
				'template' => stripslashes( $custom_field[ 'template' ] ),
			);
		}
				
		return $custom_fields;
	}
	
}