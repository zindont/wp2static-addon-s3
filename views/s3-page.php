<script>
jQuery(document).ready(function () {
    jQuery('form.disabled input').prop( "disabled", true );
});
</script>
<h2>S3 Deployment Options</h2>

<h3>S3</h3>

<form
    name="wp2static-s3-save-options"
    class="<?php if (defined('AWS_ACCESS_KEY_ID')) { echo 'disabled'; } ?>"
    method="POST"
    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field( $view['nonce_action'] ); ?>
    <input name="action" type="hidden" value="wp2static_s3_save_options" />

<table class="widefat striped">
    <tbody>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['s3Bucket']->name; ?>"
                ><?php echo $view['options']['s3Bucket']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['s3Bucket']->name; ?>"
                    name="<?php echo $view['options']['s3Bucket']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['s3Bucket']->value !== '' ? $view['options']['s3Bucket']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['s3Region']->name; ?>"
                ><?php echo $view['options']['s3Region']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['s3Region']->name; ?>"
                    name="<?php echo $view['options']['s3Region']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['s3Region']->value !== '' ? $view['options']['s3Region']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['s3AccessKeyID']->name; ?>"
                ><?php echo $view['options']['s3AccessKeyID']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['s3AccessKeyID']->name; ?>"
                    name="<?php echo $view['options']['s3AccessKeyID']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['s3AccessKeyID']->value !== '' ? $view['options']['s3AccessKeyID']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['s3SecretAccessKey']->name; ?>"
                ><?php echo $view['options']['s3SecretAccessKey']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['s3SecretAccessKey']->name; ?>"
                    name="<?php echo $view['options']['s3SecretAccessKey']->name; ?>"
                    type="password"
                    value="<?php echo $view['options']['s3SecretAccessKey']->value !== '' ?
                        \WP2Static\CoreOptions::encrypt_decrypt('decrypt', $view['options']['s3SecretAccessKey']->value) :
                        ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['s3Profile']->name; ?>"
                ><?php echo $view['options']['s3Profile']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['s3Profile']->name; ?>"
                    name="<?php echo $view['options']['s3Profile']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['s3Profile']->value !== '' ? $view['options']['s3Profile']->value : ''; ?>"
                />
            </td>
        </tr>


        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['s3RemotePath']->name; ?>"
                ><?php echo $view['options']['s3RemotePath']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['s3RemotePath']->name; ?>"
                    name="<?php echo $view['options']['s3RemotePath']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['s3RemotePath']->value !== '' ? $view['options']['s3RemotePath']->value : ''; ?>"
                />
            </td>
        </tr>

    </tbody>
</table>


<h3>CloudFront</h3>

<table class="widefat striped">
    <tbody>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['cfRegion']->name; ?>"
                ><?php echo $view['options']['cfRegion']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['cfRegion']->name; ?>"
                    name="<?php echo $view['options']['cfRegion']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['cfRegion']->value !== '' ? $view['options']['cfRegion']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['cfAccessKeyID']->name; ?>"
                ><?php echo $view['options']['cfAccessKeyID']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['cfAccessKeyID']->name; ?>"
                    name="<?php echo $view['options']['cfAccessKeyID']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['cfAccessKeyID']->value !== '' ? $view['options']['cfAccessKeyID']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['cfSecretAccessKey']->name; ?>"
                ><?php echo $view['options']['cfSecretAccessKey']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['cfSecretAccessKey']->name; ?>"
                    name="<?php echo $view['options']['cfSecretAccessKey']->name; ?>"
                    type="password"
                    value="<?php echo $view['options']['cfSecretAccessKey']->value !== '' ?
                        \WP2Static\CoreOptions::encrypt_decrypt('decrypt', $view['options']['cfSecretAccessKey']->value) :
                        ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['cfProfile']->name; ?>"
                ><?php echo $view['options']['cfProfile']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['cfProfile']->name; ?>"
                    name="<?php echo $view['options']['cfProfile']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['cfProfile']->value !== '' ? $view['options']['cfProfile']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['cfDistributionID']->name; ?>"
                ><?php echo $view['options']['cfDistributionID']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['cfDistributionID']->name; ?>"
                    name="<?php echo $view['options']['cfDistributionID']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['cfDistributionID']->value !== '' ? $view['options']['cfDistributionID']->value : ''; ?>"
                />
            </td>
        </tr>


    </tbody>
</table>

<br>

    <button class="button btn-primary">Save S3 Options</button>
</form>

