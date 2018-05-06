<template>
  <div class="foc-csv-export">
    <div class="row">
      <div class="col-md-12">
        <div class="row">
          <div class="col-md-8">
            <h1>{{ $t('Export submodule') }}</h1>
          </div>
          <div class="col-md-4 text-right">
            <button :disabled="true" @click.prevent="submitExportData" class="btn btn-warning btn-lg"><i class="fa fa-rocket"></i> {{ $t('Start export!') }}</button>
          </div>
        </div>
        <hr>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data () {
    return {
      msg: 'Export'
    }
  },
  methods: {
    submitExportData () {

    }
  }
}
</script>
