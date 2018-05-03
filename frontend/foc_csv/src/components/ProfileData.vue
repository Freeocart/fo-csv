<template>
<div class="row">
  <div class="col-md-4">
    <div class="panel panel-primary">
      <div class="panel-heading">
        {{ $t('Backup utils') }}
      </div>

      <div class="panel-body">
        <div class="form-group">
          <button class="btn btn-default" @click.prevent="showProfileState = !showProfileState">{{ $t('Show current profile data') }}</button>

          <textarea :value="JSON.stringify($store.state.profile)" class="form-control" v-if="showProfileState"></textarea>
        </div>

        <div class="form-group">
          <button class="btn btn-default" @click.prevent="showProfilesState = !showProfilesState">{{ $t('Show profiles data') }}</button>

          <textarea :value="JSON.stringify($store.state.data.profiles)" class="form-control" v-if="showProfilesState"></textarea>
        </div>

        <div class="form-group">
          <button class="btn btn-default" @click.prevent="showAllDataState = !showAllDataState">{{ $t('Show all data state') }}</button>

          <textarea :value="JSON.stringify($store.state.data)" class="form-control" v-if="showAllDataState"></textarea>
        </div>
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
import ProfilesControlList from '@/components/ProfilesControlList'

export default {
  components: {
    ProfilesControlList
  },
  data () {
    return {
      showProfileState: false,
      showProfilesState: false,
      showAllDataState: false
    }
  },
  methods: {
    restoreToProfile () {
      let name = this.$refs.restore_profile_name.value
      let profile = JSON.parse(this.$refs.restore_profile_data.value)

      this.$store.dispatch('applyProfile', { name, profile })
      this.$store.dispatch('saveNewProfile', name)
    },
    restoreProfiles () {
      if (confirm(this.$t('Are you sure? This will remove all profiles before trying to add new ones!'))) {
        try {
          let profiles = JSON.parse(this.$refs.restore_profiles_data.value)

          // keep default profile if user removed it
          if (!profiles['default']) {
            profiles['default'] = this.$store.state.data.profiles.default
          }
          this.$store.dispatch('saveAllProfiles', profiles)
          // for (let name in profiles) {
          //   let profile = profiles[name]
          //   this.$store.dispatch('applyProfile', { name, profile })
          //   this.$store.dispatch('saveNewProfile', name)
          // }
        }
        catch (e) {
          console.error(e)
        }
      }
    }
  }
}
</script>
