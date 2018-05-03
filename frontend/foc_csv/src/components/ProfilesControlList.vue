<template>
  <div>
    <label class="label label-default">{{ $t('Profile control') }}</label>
    <table class="table table-bordered">
      <tbody>
        <tr v-for="(profile, name, idx) in $store.state.data.profiles" :key="idx">
          <td>
            <strong>{{ name }}</strong>
          </td>
          <td class="text-right">
            <span :disabled="name === 'default'" class="btn btn-danger" @click.prevent="removeProfile(name)"><i class="fa fa-trash"></i></span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  methods: {
    removeProfile (name) {
      if (confirm(this.$t('Are you sure you want remove this item?'))) {
        if (this.$store.getters.currentProfileName === name) {
          this.$store.dispatch('setCurrentProfileName', 'default')
        }

        this.$store.dispatch('deleteProfile', name)
        this.$store.dispatch('saveAllProfiles', this.$store.getters.profiles)
      }
    }
  }
}
</script>
