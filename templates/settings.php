<?php

namespace Netzstrategen\ShopStandards;
?>

<div class="wrap">
  <h2><?= __('Shop Standards settings', Plugin::L10N) ?></h2>
  <form method="post" action="options.php" class="<?= Plugin::PREFIX ?>-form">
    <?php settings_fields(Plugin::L10N . '-settings'); ?>
    <h2><?= __('SEO related settings', Plugin::L10N) ?></h2>
    <table class="form-table">
      <tbody>
        <?php foreach (Admin::$pluginSettings as $key => $setting): ?>
          <tr>
            <th scope="row"><?= $setting['label'] ?></th>
            <td>
              <fieldset>
                <label for="<?= Plugin::L10N . $key ?>">
                  <input
                    name="<?= Plugin::L10N . $key ?>"
                    id="<?= Plugin::L10N . $key ?>"
                    type="checkbox"
                    value="1"
                    <?php checked(get_option(Plugin::L10N . $key)) ?>
                  >
                  <p class="description"><?= $setting['label'] ?></p>
                </label>
              </fieldset>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php submit_button(); ?>
  </form>
</div>
