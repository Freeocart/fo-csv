<template>
<div>
  <label class="label label-default">{{ $t('Profile control') }}</label>
  <table class="table table-bordered">
    <tbody>
      <tr v-for="(profile, name, idx) in profiles" :key="idx">
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
export default {
  props: {
    profiles: {
      type: Object,
      required: true
    }
  },
  methods: {
    removeProfile (name) {
      this.$emit('deleteProfile', name)
    }
  }
}
</script>
