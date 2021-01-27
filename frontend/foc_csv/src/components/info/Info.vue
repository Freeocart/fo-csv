<template>
<div>
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-danger">
        <div class="panel-heading">
          <h3 class="panel-title">{{ $t('Disclaimer') }}</h3>
        </div>
        <div class="panel-body">
          <p v-html="$t('Use this software at your own risk!')">
          </p>
          <p v-html="$t('Author or contributors are not responsible at data corruption/remove caused by using this software.')">
          </p>
          <p>
            <strong v-html="$t('We are strongly recommend you to backup data before using this.')"></strong>
          </p>
          <p v-html="$t('Source code distributed \'as is\', all parts of the software is free and opensource and respecting GPLv3 license.')">
          </p>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-8">
      <div class="panel panel-primary">
        <div class="panel-heading">
          {{ $t('How to use') }}
        </div>
        <div class="panel-body">
          <p v-html="$t('Module <strong>{name}</strong> targets to be a maximum flexible for import/export CSV data, without any restrictions in CSV format.', { name: $appName })">
          </p>
          <p v-html="$t('However, more flexibility costs more complexity, so if you not sure what you do, please, entrust the work to more qualified employee!')">
          </p>
          <p v-html="$t('Please, look for instructions below')">
          </p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="panel panel-success">
        <div class="panel-heading">
          {{ $t('Application information') }}
        </div>
        <div class="panel-body">
          <p>
            <strong>{{ $appName }}</strong> <label class="label label-primary">{{ $appVersion }}</label>
          </p>

          <p v-html="$t('Thank you for choosing <strong>{name}</strong> opencart module!', { name: $appName })">
          </p>

          <p v-html="$t('I hope, my work will be useful for you')">
          </p>

          <hr>

          <p v-html="$t('If you have any questions, found bugs, or want request a features, you can send me email, or use github issues to inform me.')">
          </p>

          <a class="btn btn-primary btn-block" href="https://freeocart.ru/" target="_blank"><i class="fa fa-link"></i> <strong>Freeocart.ru</strong></a>

          <a class="btn btn-default btn-block" href="https://github.com/ikenfin/fo-csv" target="_blank"><i class="fa fa-github"></i> Github</a>

          <a class="btn btn-default btn-block" :href="'mailto:' + $t('focsv-support@freeocart.ru')"><i class="fa fa-envelope"></i> {{ $t('focsv-support@freeocart.ru') }}</a>
        </div>
      </div>

    </div>
  </div>
</div>
</template>

<script>
export default {
}
</script>
