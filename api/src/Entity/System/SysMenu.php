<?php

namespace App\Entity\System;

use App\Entity\BaseEntity;
use App\Repository\System\SysMenuRepository;
use App\Service\Logger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysMenuRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysMenu extends BaseEntity
{
    public const TYPE_CATEGORY = 1;
    public const TYPE_MENU = 2;
    public const TYPE_EXTERNAL_URL = 3;
    public const TYPE_BUTTON = 4;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $t = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    #[ORM\Column(length: 128)]
    private ?string $routePath = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $component = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $perm = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $visible = null;

    #[ORM\Column(nullable: true)]
    private ?int $sort = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $redirect = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $alwaysShow = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?bool $keepAlive = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $blank = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $noAuth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $params = null;


    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children', fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    private ?SysMenu $parent = null;

    /**
     * @var Collection<int,SysMenu>;
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ["remove"], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(["sort" => "ASC"])]
    private ?Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
    public static function create(array $data): SysMenu
    {
        $menu = new self();
        return $menu->_setProps($data);
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getParent(): ?SysMenu
    {
        return $this->parent;
    }

    /**
     * 设置父级菜单
     * @param SysMenu|null $parent 父级菜单对象，null 表示根目录
     */
    public function setParent(?SysMenu $parent): static
    {
        $this->parent = $parent;
        return $this;
    }
    public function getT(): ?string
    {
        return $this->t ?: $this->name;
    }

    public function setT(string $t): static
    {
        $this->t = $t;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }


    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getRoutePath(): ?string
    {
        return $this->routePath;
    }

    public function setRoutePath(string $path): static
    {
        // 规范化路径：去除多余斜杠和首尾空白
        $path = trim(preg_replace('#/+#', '/', $path), '/');

        // 根级节点确保以 '/' 开头
        if (!$this->parent && $path !== '') {
            $path = '/' . $path;
        }
        $this->routePath = $path;
        return $this;
    }


    /**
     * 只有当菜单有父级时，才会在路由前面添加父级路由
     * 当类型为目录时，返回目录路由
     * 当类型为菜单时，返回菜单路由
     */
    public function getFullRoutePath(): string
    {
        return match ($this->type) {
            self::TYPE_CATEGORY, self::TYPE_MENU => $this->parent?->getFullRoutePath() . "/" . $this->getRoutePath(),
            default => $this->getRoutePath()
        };
    }

    public function setParams(array $params): static
    {
        $this->params = json_encode($params);
        return $this;
    }

    public function getParams(): array
    {
        return $this->params ? json_decode($this->params, true) : [];
    }

    public function getParamsObj(): array
    {
        $params = $this->getParams();
        $newArray = [];
        if (!$params) return $newArray;
        foreach ($params as $p) {
            $newArray[$p['key']] = $p['value'];
        }
        return $newArray;
    }


    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function setComponent(?string $component): static
    {
        if ($this->getType() == SELF::TYPE_CATEGORY) {
            $this->component = "Layout";
        } else {
            $this->component = $component;
        }
        return $this;
    }

    public function getPerm(): ?string
    {
        return $this->perm;
    }

    public function setPerm(?string $perm): static
    {
        $this->perm = $perm;

        return $this;
    }

    public function getVisible(): ?int
    {
        return $this->visible;
    }

    public function setVisible(int $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getRedirect(): ?string
    {
        return $this->redirect;
    }

    public function setRedirect(?string $redirect): static
    {
        $this->redirect = $redirect;

        return $this;
    }

    public function getAlwaysShow(): ?int
    {
        return $this->alwaysShow;
    }

    public function setAlwaysShow(?int $alwaysShow): static
    {
        $this->alwaysShow = $alwaysShow;

        return $this;
    }

    public function getKeepAlive(): ?bool
    {
        return $this->keepAlive ? true : false;
    }

    public function setKeepAlive(?int $keepAlive): static
    {
        $this->keepAlive = $keepAlive;

        return $this;
    }

    public function getBlank(): ?int
    {
        return $this->blank;
    }

    public function setBlank(?int $blank): static
    {
        $this->blank = $blank ? 1 : 0;

        return $this;
    }


    public function getNoAuth(): ?int
    {
        return $this->noAuth;
    }

    public function setNoAuth(?int $noAuth): static
    {
        $this->noAuth = $noAuth;

        return $this;
    }

    public function getRoute(): array
    {
        $retRoute = [
            "path" => $this->getRoutePath(),
            "component" => $this->getComponent(),
            "name" => $this->getRouteName(),
            "meta" => [
                "name" => $this->getName(),
                "title" => $this->getT(),
                "params" => $this->getParamsObj(),
                "icon" => $this->getIcon(),
                "hidden" => $this->getVisible() ? false : true,
                "keepAlive" => $this->getKeepAlive() ? true : false,
                "blank" => $this->getBlank(),
                "noAuth" => $this->getNoAuth(),
            ],
        ];
        if ($this->getRedirect())  $retRoute["redirect"] = $this->getRedirect();
        if ($this->getAlwaysShow()) $retRoute["meta"]["alwaysShow"] = true;
        return $retRoute;
    }

    //根据路由路径获取路由名称,去掉路由参数，然后将'/'替换为'.';
    public function getRouteName()
    {
        // //获取:左边的部分
        $rawName = explode(":", $this->getFullRoutePath())[0];
        $pathArray = array_filter(explode("/", $rawName));
        $name = "";
        foreach ($pathArray as $path) {
            $name .= ucfirst($path);
        }
        return $name;
    }

    /**
     * @return Collection<int,SysMenu>
     */
    public function getChildren(): Collection
    {
        return $this->children ?: new ArrayCollection();
    }

    public function setChildren(?Collection $menu): static
    {
        $this->children = $menu;
        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "parentId" => $this->parent ? $this->parent->getId() : 0,
            "name" => $this->getName(),
            "sort" => $this->getSort(),
            "icon" => $this->getIcon(),
            "title" => $this->getT(),
            "t" => $this->getT(),
            "routePath" => $this->getRoutePath(),
            "routeName" => $this->getRouteName(),
            "alwaysShow" => $this->getAlwaysShow(),
            'blank' => $this->getBlank(),
            "component" => $this->getComponent(),
            "visible" => $this->getVisible(),
            "redirect" => $this->getRedirect(),
            "type" => $this->getType(),
            "perm" => $this->getPerm(),
            "keepAlive" => $this->getKeepAlive(),
            "params" => $this->getParams(),
            "noAuth" => $this->getNoAuth()
        ];
    }
}
