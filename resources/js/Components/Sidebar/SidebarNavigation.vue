<template>
  <SidebarGroup v-for="item in navigation">
    <template v-if="item.hasChildren">
      <SidebarGroupLabel v-if="item.title">{{ item.title }}</SidebarGroupLabel>
      <SidebarGroupContent>
        <SidebarMenu>
          <template v-for="child in item.children">
            <template v-if="child.hasChildren">
              <Collapsible :default-open="child.isChildActive" class="group/collapsible">
                <SidebarMenuItem>
                  <CollapsibleTrigger as-child>
                    <SidebarNavigationButton :item="child" :as="SidebarMenuButton">
                      <ChevronRightIcon class="size-4 ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarNavigationButton>
                  </CollapsibleTrigger>
                  <CollapsibleContent>
                    <SidebarMenuSub>
                      <template v-for="subChld in child.children">
                        <SidebarNavigationButton
                          :item="subChld"
                          :as="SidebarMenuSubButton"
                          :is-active="subChld.isActive || subChld.isChildActive"
                        />
                      </template>
                    </SidebarMenuSub>
                  </CollapsibleContent>
                </SidebarMenuItem>
              </Collapsible>
            </template>
            <template v-else>
              <SidebarMenuItem>
                <SidebarNavigationButton :item="child" :as="SidebarMenuButton" :is-active="child.isActive" />
              </SidebarMenuItem>
            </template>
          </template>
        </SidebarMenu>
      </SidebarGroupContent>
    </template>
    <template v-else>
      <SidebarGroupContent>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarNavigationButton :item="item" :as="SidebarMenuButton" :is-active="item.isActive" />
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroupContent>
    </template>
  </SidebarGroup>
</template>

<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/Collapsible'
import { computed } from 'vue'
import SidebarMenuItem from './SidebarMenuItem.vue'
import SidebarGroup from './SidebarGroup.vue'
import SidebarGroupContent from './SidebarGroupContent.vue'
import SidebarGroupLabel from './SidebarGroupLabel.vue'
import SidebarMenu from './SidebarMenu.vue'
import SidebarMenuSub from './SidebarMenuSub.vue'
import SidebarNavigationButton from './SidebarNavigationButton.vue'
import SidebarMenuButton from './SidebarMenuButton.vue'
import SidebarMenuSubButton from './SidebarMenuSubButton.vue'
import { ChevronRightIcon } from 'lucide-vue-next'
import { type Menu, useNavigation } from '@stacktrace/ui'

const props = defineProps<{
  menu: Menu
}>()

const navigation = useNavigation(computed(() => props.menu))
</script>
