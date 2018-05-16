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
        <div class="form-group">
          <label class="label label-default">{{ $t('Restore state to profile') }}</label>
          <input ref="restore_profile_name" :placeholder="$t('Profile name')" type="text" class="form-control">
          <textarea ref="restore_profile_data" class="form-control"></textarea>

          <button class="btn btn-primary" @click.prevent="restoreToProfile"><i class="fa fa-floppy-o"></i> {{ $t('Restore') }}</button>
        </div>

        <div class="form-group">
          <label class="label label-default">{{ $t('Restore profiles') }}</label>

          <textarea ref="restore_profiles_data" class="form-control"></textarea>

          <button class="btn btn-danger" @click.prevent="restoreProfiles"><i class="fa fa-floppy-o"></i> {{ $t('Restore') }}</button>
        </div>
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
          <profiles-control-list></profiles-control-list>
        </div>
      </div>
    </div>
  </div>
</div>
</template>

<script>
import ProfilesControlList from './ProfilesControlList'
import SerializedDataToggler from './SerializedDataToggler'

import { createNamespacedHelpers } from 'vuex'

const { mapActions, mapState } = createNamespacedHelpers('importer')

export default {
  components: {
    ProfilesControlList,
    SerializedDataToggler
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
      'profile',
      'data'
    ])
  },
  methods: {
    restoreToProfile () {
      let name = this.$refs.restore_profile_name.value
      let profile = JSON.parse(this.$refs.restore_profile_data.value)

      this.applyProfile({ name, profile })
      this.saveNewProfile(name)
    },
    restoreProfiles () {
      if (confirm(this.$t('Are you sure? This will remove all profiles before trying to add new ones!'))) {
        try {
          let profiles = JSON.parse(this.$refs.restore_profiles_data.value)

          // keep default profile if user removed it
          if (!profiles['default']) {
            profiles['default'] = this.data.profiles.default
          }
          this.saveAllProfiles(profiles)
        }
        catch (e) {
          console.error(e)
        }
      }
    },
    ...mapActions([
      'applyProfile',
      'saveNewProfile',
      'saveAllProfiles'
    ])
  }
}
</script>
