<template>
<div class="row">
  <div class="col-md-4">
    <div class="panel panel-primary">
      <div class="panel-heading">
        {{ $t('Backup utils') }}
      </div>

      <div class="panel-body">
        <serialized-data-toggler :value="profile">
          {{ $t('Show current profile data') }}
        </serialized-data-toggler>

        <serialized-data-toggler :value="profiles">
          {{ $t('Show profiles data') }}
        </serialized-data-toggler>

        <serialized-data-toggler :value="data">
          {{ $t('Show all data state') }}
        </serialized-data-toggler>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="panel panel-primary">
      <div class="panel-heading">
        {{ $t('Restore utils') }}
      </div>

      <div class="panel-body">
        <restore-profile @restore="restoreToProfile($event)">
          {{ $t('Restore state to profile') }}
        </restore-profile>

        <restore-profiles :profiles="profiles" @restore="restoreProfiles($event)">
          {{ $t('Restore profiles') }}
        </restore-profiles>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="panel panel-danger">
      <div class="panel-heading">
        {{ $t('Remove utils') }}
      </div>
      <div class="panel-body">
        <profiles-control-list :profiles="profiles" @deleteProfile="deleteProfile($event)"></profiles-control-list>
      </div>
    </div>
  </div>
</div>
</template>

<script>
import ProfilesControlList from './ProfilesControlList'
import SerializedDataToggler from './SerializedDataToggler'
import RestoreProfile from './RestoreProfile'
import RestoreProfiles from './RestoreProfiles'
import utilMixin from '@/mixins/util'

export default {
  components: {
    ProfilesControlList,
    SerializedDataToggler,
    RestoreProfile,
    RestoreProfiles
  },
  mixins: [
    utilMixin('exporter')
  ]
}
</script>
