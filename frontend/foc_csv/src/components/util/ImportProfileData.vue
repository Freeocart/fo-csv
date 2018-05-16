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

        <serialized-data-toggler :value="data.profiles">
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

        <restore-profiles @restore="restoreProfiles">
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
        <div class="form-group">
          <profiles-control-list :profiles="profiles" @deleteProfile="deleteProfile($event)"></profiles-control-list>
        </div>
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

import { createNamespacedHelpers } from 'vuex'

const { mapActions, mapState, mapGetters } = createNamespacedHelpers('importer')

export default {
  components: {
    ProfilesControlList,
    SerializedDataToggler,
    RestoreProfile,
    RestoreProfiles
  },
  data () {
    return {
      showProfileState: false,
      showProfilesState: false,
      showAllDataState: false
    }
  },
  computed: {
    ...mapState([
      'data'
    ]),
    ...mapGetters([
      'profile',
      'profiles'
    ])
  },
  methods: {
    deleteProfile (name) {
      if (confirm(this.$t('Are you sure you want remove this item?'))) {
        if (this.currentProfileName === name) {
          this.setCurrentProfileName('default')
        }

        this.deleteProfile(name)
        this.saveAllProfiles(this.profiles)
      }
    },
    restoreToProfile ({ name, profile }) {
      this.applyProfile({ name, profile })
      this.saveNewProfile(name)
    },
    restoreProfiles (profiles) {
      if (confirm(this.$t('Are you sure? This will remove all profiles before trying to add new ones!'))) {
        this.saveAllProfiles(profiles)
      }
    },
    ...mapActions([
      'applyProfile',
      'saveNewProfile',
      'deleteProfile',
      'saveAllProfiles',
      'setCurrentProfileName'
    ])
  }
}
</script>
