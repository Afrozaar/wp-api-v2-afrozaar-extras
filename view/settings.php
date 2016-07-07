<div class="aws-content aws-settings">

	<h3><?php _e( 'Access Keys', 'afrozaar_extras' ); ?></h3>

	<p>
			<?php _e( 'We recommend defining your Access Keys in wp-config.php so long as you don&#8217;t commit it to source control (you shouldn&#8217;t be). Simply copy the following snippet and replace the stars with the keys.', 'afrozaar_extras' ); ?>
		</p>

		<pre>define( 'DBI_AWS_ACCESS_KEY_ID', '********************' );
define( 'DBI_AWS_SECRET_ACCESS_KEY', '****************************************' );</pre>

		<p class="reveal-form">
			<?php _e( 'If you&#8217;d rather store your Access Keys in the database, <a href="">click here to reveal a form.</a>', 'afrozaar_extras' ); ?>
		</p>

		<form method="post" <?php echo ( ! $this->get_access_key_id() && ! $this->get_secret_access_key() ) ? 'style="display: none;"' : ''; // xss ok ?>>

			<?php if ( isset( $_POST['access_key_id'] ) ) { // input var okay ?>
				<div class="aws-updated updated">
					<p><strong>Settings saved.</strong></p>
				</div>
			<?php } ?>

			<input type="hidden" name="action" value="save" />
			<?php wp_nonce_field( 'afro-save-settings' ) ?>

			<table class="form-table">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Access Key ID:', 'afrozaar_extras' ); ?></th>
					<td>
						<input type="text" name="access_key_id" value="<?php echo $this->get_access_key_id() // xss ok; ?>" size="50" autocomplete="off" />
					</td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Secret Access Key:', 'afrozaar_extras' ); ?></th>
					<td>
						<input type="text" name="secret_access_key" value="<?php echo $this->get_secret_access_key() ? '-- not shown --' : ''; // xss ok ?>" size="50" autocomplete="off" />
					</td>
				</tr>

				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Region:', 'afrozaar_extras' ); ?></th>
					<td>
						<input type="text" name="aws_region" value="<?php echo $this->get_aws_region() // xss ok; ?>" size="50" autocomplete="off" />
					</td>
				</tr>

				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'New post Topic:', 'afrozaar_extras' ); ?></th>
					<td>
						<input type="text" name="new_post_topic" value="<?php echo $this->get_new_post_topic() // xss ok; ?>" size="50" autocomplete="off" />
					</td>
				</tr>

				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Updated post Topic:', 'afrozaar_extras' ); ?></th>
					<td>
						<input type="text" name="updated_post_topic" value="<?php echo $this->get_updated_post_topic() // xss ok; ?>" size="50" autocomplete="off" />
					</td>
				</tr>

				<tr valign="top">
					<th colspan="2" scope="row">
						<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'afrozaar_extras' ); ?></button>
						<?php if ( $this->get_access_key_id() || $this->get_secret_access_key() ) : ?>
							&nbsp;
							<button class="button remove-keys"><?php _e( 'Remove Keys', 'afrozaar_extras' ); ?></button>
						<?php endif; ?>
					</th>
				</tr>
			</table>

		</form>

</div>
