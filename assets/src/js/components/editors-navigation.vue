<template>
    <div id="trp-editors-navigation-tabs">
        <table id="trp-editors-tab-table">
            <td class="trp-editors-tab" :class="{'trp-selected-tab': navigationTab.handle === selectedTab }" v-for="navigationTab in navigationTabs">
                <a :href="navigationTab.path">{{navigationTab.label}}</a>
            </td>
        </table>
    </div>
</template>
<script>
export default{
    props:[
        'navigationTabs',
        'selectedTab'
    ],
    methods:{

    }
}
</script>
