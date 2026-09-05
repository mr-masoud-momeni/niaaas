<h2 style="background-color: #2666cf;color: white; padding: 10px; border-radius: 10px;"><?php esc_html_e('اطلاعات کاربر (پلاگین پیامکی نیاس)', 'nias-login-signup'); ?> </h2>
<table style="width: 100%;
background-color: #2666cf59;
padding: 10px;
border-radius: 10px;
color: #2666cf;
border: 1px solid #2666cf;">
    <tbody>
            <tr>
                <th><label for="phone"> <?php esc_html_e('شماره تلفن', 'nias-login-signup'); ?> </label>  </th>
                <td>
                    <input type="text" inputmode="tel" name="nias_phone" placeholder="<?php esc_attr_e('شماره همراه', 'nias-login-signup'); ?>" autocomplete="off" role="presentation" class="regular-text" value="<?php echo esc_attr( $phone ); ?>">

                </td>

            </tr>




        
    </tbody>




</table>