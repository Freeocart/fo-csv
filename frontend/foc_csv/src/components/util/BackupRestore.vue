<template>
<div>
  <div class="panel panel-primary">
    <div class="panel-heading">
      {{ $t('Import utils') }}
    </div>
    <import-profile-data class="panel-body"></import-profile-data>
  </div>

  <div class="panel panel-primary">
    <div class="panel-heading">
      {{ $t('Export utils') }}
    </div>
    <export-profile-data class="panel-body"></export-profile-data>
  </div>
</div>
</template>

<script>
import ImportProfileData from './ImportProfileData'
import ExportProfileData from './ExportProfileData'

export default {
  components: {
    ImportProfileData,
    ExportProfileData
  }
}
</script>
