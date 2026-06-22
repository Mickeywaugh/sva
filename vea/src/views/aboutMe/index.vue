<template>
  <div>
    <h1>Mickey's Coding Histories.</h1>
    <p></p>
  </div>
  <div>
    <template>
      <el-timeline>
        <el-timeline-item center timestamp="2024-02 ~ Now" placement="top">
          <el-card>
            <h4>博应信息技术有限公司(BoingTech Inc.)</h4>
            <p></p>
          </el-card>
        </el-timeline-item>
        <el-timeline-item timestamp="2020-12 ~ 2023-10" placement="top">
          <el-card>
            <h4>川源信息技术有限公司(AccelorStor Inc.)</h4>
            <p></p>
          </el-card>
        </el-timeline-item>
        <el-timeline-item center timestamp="2017-06 ~ 2020-10" placement="top">
          <el-card>
            <h4>上飞飞机装备制造有限公司(SAEM)</h4>
            <p></p>
          </el-card>
        </el-timeline-item>
      </el-timeline>
    </template>
  </div>
</template>

<script setup lang="ts">

  const t = useI18n();
  
</script>