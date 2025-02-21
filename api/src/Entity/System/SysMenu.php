<?php

namespace App\Entity\System;

use App\Entity\EntityBase;
use App\Repository\System\SysMenuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysMenuRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysMenu extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $parentId = 0;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $i18nIndex = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    #[ORM\Column(length: 128)]
    private ?string $routePath = null;

    #[ORM\Column(length: 128)]
    private ?string $routeName = null;

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
    private ?int $keepAlive = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $blank = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'child')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    private ?SysMenu $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ["remove"])]
    #[ORM\OrderBy(["sort" => "ASC"])]
    private $child = [];

    const TypeMap = [1 => 'CATALOG', 2 => 'MENU', 3 => 'EXTLINK', 4 => 'BUTTON'];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): static
    {
        $this->parentId = $parentId;

        return $this;
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
    public function setParent(?SysMenu $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
    public function getI18nIndex(): ?string
    {
        return $this->i18nIndex ?: $this->name;
    }

    public function setI18nIndex(string $t): static
    {
        $this->i18nIndex = $t;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTypeCode(): ?string
    {

        return  $this->type ? self::TypeMap[$this->type] : "NULL";
    }

    public function setType(string $type): static
    {
        $this->type = array_search($type, self::TypeMap);

        return $this;
    }

    public function getRoutePath(): ?string
    {
        return $this->routePath;
    }

    public function setRoutePath(string $path): static
    {
        //如果是新窗体打开页面，则不使用路由嵌套，路径自身需要加‘/’
        //如果不是新窗体打开，则使用路由嵌套，去掉路径前缀
        if ($this->blank || $this->getParentId() == 0) {
            if ($path && $path[0] != "/") $path = "/" . $path;
        } else {
            //去掉路由前缀
            $path = rtrim($path, '/');
            $path = strpos($path, "/") === 0 ? ltrim($path, '/') : $path;
        }
        $this->routePath = $path;
        return $this;
    }

    public function getComponent(): ?string
    {
        return $this->type == 1 && $this->getParentId() == 1 ?  $this->component : "Layout";
    }

    public function setComponent(?string $component): static
    {
        $this->component = $component;

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

    public function getKeepAlive(): ?int
    {
        return $this->keepAlive;
    }

    public function setKeepAlive(?int $keepAlive): static
    {
        $this->keepAlive = $keepAlive;

        return $this;
    }

    public function getBlank(): ?int
    {
        return $this->blank ? true : false;
    }

    public function setBlank(?int $blank): static
    {
        $this->blank = $blank;

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
                "title" => $this->getI18nIndex(),
                "icon" => $this->getIcon(),
                "hidden" => $this->getVisible() ? false : true,
                "keepAlive" => $this->getKeepAlive() ? true : false,
                "blank" => $this->getBlank() ? true : false,
            ],
        ];
        if ($this->getRedirect())  $retRoute["redirect"] = $this->getRedirect();
        if ($this->getAlwaysShow()) $retRoute["meta"]["alwaysShow"] = true;
        return $retRoute;
    }


    public function setRouteName(string $routeName): self
    {

        $route = $routeName ?? implode(".", array_filter(explode("/", $this->getRoutePath())));
        $this->routeName = $route;
        return $this;
    }

    //根据路由路径获取路由名称,去掉路由参数，然后将'/'替换为'.';
    public function getRouteName()
    {
        return $this->routeName;
    }

    public function getChild()
    {
        return  $this->child ?: false;
    }

    public function setChild($menu): static
    {
        $this->child = $menu;
        return $this;
    }

    public function toArray()
    {
        return [
            "id" => $this->getId(),
            "parentId" => $this->getParentId(),
            "name" => $this->getName(),
            "sort" => $this->getSort(),
            "icon" => $this->getIcon(),
            "title" => $this->getI18nIndex(),
            "routePath" => $this->getRoutePath(),
            "routeName" => $this->getRouteName(),
            "alwaysShow" => $this->getAlwaysShow(),
            'blank' => $this->getBlank(),
            "component" => $this->getComponent(),
            "visible" => $this->getVisible(),
            "redirect" => $this->getRedirect(),
            "type" => $this->getTypeCode(),
            "perm" => $this->getPerm()
        ];
    }
}
