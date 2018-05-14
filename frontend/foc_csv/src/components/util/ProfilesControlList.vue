<template>
  <div>
    <label class="label label-default">{{ $t('Profile control') }}</label>
    <table class="table table-bordered">
      <tbody>
        <tr v-for="(profile, name, idx) in data.profiles" :key="idx">
          <td>
            <strong>{{ name }}</strong>
          </td>
          <td class="text-right">
            <span v-if="name !== 'default'" class="btn btn-danger" @click.prevent="removeProfile(name)">
              <i class="fa fa-trash"></i>
            </span>
            <span v-else :disabled="true" class="btn btn-danger">
              <i class="fa fa-trash"></i>
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { createNamespacedHelpers } from 'vuex'

const { mapGetters, mapActions, mapState } = createNamespacedHelpers('importer')

export default {
  computed: {
    ...mapGetters([
      'currentProfileName',
      'profiles'
    ]),
    ...mapState([
      'data'
    ])
  },
  methods: {
    removeProfile (name) {
      if (confirm(this.$t('Are you sure you want remove this item?'))) {
        if (this.currentProfileName === name) {
          this.setCurrentProfileName('default')
        }

        this.deleteProfile(name)
        this.saveAllProfiles(this.profiles)
      }
    },
    ...mapActions([
      'deleteProfile',
      'saveAllProfiles',
      'setCurrentProfileName'
    ])
  }
}
</script>
