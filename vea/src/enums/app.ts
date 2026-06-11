export type StatusTag = {
  type: "danger" | "info" | "primary" | "success" | "warning";
  effect: "light" | "dark" | "plain";
  label: string;
  icon?: string;
};

export type StatusMap = Map<number, StatusTag>;

export const EnableMap = new Map<number, StatusTag>([
  [1, { type: "success", effect: "dark", label: "common.yes" }],
  [0, { type: "info", effect: "dark", label: "common.no" }],
]);

export const ApiCodeMap = new Map<number, StatusTag>([
  [0, { type: "success", effect: "light", label: "正常" }],
  [1, { type: "warning", effect: "dark", label: "异常" }],
  [-1, { type: "info", effect: "dark", label: "未知" }],
]);

export const enum BooleanStatusEnum {
  FALSE = 0,
  TRUE = 1,
}

export const BooleanStatusMap = new Map<number, StatusTag>([
  [BooleanStatusEnum.FALSE, { type: "info", effect: "dark", label: "否" }],
  [BooleanStatusEnum.TRUE, { type: "success", effect: "dark", label: "是" }],
]);
