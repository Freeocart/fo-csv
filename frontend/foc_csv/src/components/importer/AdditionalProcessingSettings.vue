<template>
  <div class="panel panel-primary">
    <div class="panel-heading">
      {{ $t('Additional processing settings') }}
    </div>
    <div class="panel-body">
      <multicolumn-extractor></multicolumn-extractor>
    </div>
  </div>
</template>

<script>
import MulticolumnExtractor from './MulticolumnExtractor'

export default {
  components: {
    MulticolumnExtractor
  }
}
</script>
