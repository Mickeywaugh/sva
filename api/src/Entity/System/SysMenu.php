<?php

namespace App\Entity\System;

use App\Entity\BaseEntity;
use App\Repository\System\SysMenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use EasyWeChat\Kernel\Support\Arr;

#[ORM\Entity(repositoryClass: SysMenuRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysMenu extends BaseEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private ?int $parentId = 0;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $type = null;

    #[ORM\Column(length: 128)]
    private ?string $routePath = null;

    #[ORM\Column(length: 128)]
    private ?string $routeName = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $component = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $treePath = null;

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

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $isPublic = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $params = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    private ?SysMenu $parent = null;

    /**
     * @var Collection<int,SysMenu>;
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ["remove"])]
    #[ORM\OrderBy(["sort" => "ASC"])]
    private ?Collection $children = null;

    public static function create(array $data): SysMenu
    {
        $menu = new self();
        return $menu->_setProps($data);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId = 0): static
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
        $this->setParentId($parent ? $parent->getId() : 0);
        $this->setTreePath();
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }


    public function setType(string $type): static
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
        //如果是子菜单，路由需要加上父级路由，如果不是，则路由确保路由前缀是‘/’

        //拆分$path为数组，并去掉数组值为空的元素
        $pathArray = array_filter(explode('/', $path));
        $path = implode('/', $pathArray);
        if ($this->getParentId() == 0) {
            $path = '/' . $path;
        }
        $this->routePath = $path;
        return $this;
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

    public function setTreePath(): static
    {   //
        if (!$this->parent) {
            $treePathArray = [0];
        } else {
            $treePathArray = $this->parent->getTreePath() + [$this->parent->getId()];
        }
        $this->treePath = implode(",", $treePathArray);

        return $this;
    }

    public function getTreePath(): ?array
    {
        return explode(",", $this->parentId == 0 ? "0" : $this->treePath);
    }

    public function getComponent(): ?string
    {
        return $this->component ?: ($this->type == "C" ? "Layout" : "");
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
        return $this->alwaysShow ?: 1;
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
        return $this->blank;
    }

    public function setBlank(?int $blank): static
    {
        $this->blank = $blank ? 1 : 0;

        return $this;
    }


    public function getIsPublic(): ?int
    {
        return $this->isPublic ?: 0;
    }

    public function setIsPublic(?int $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getRoute(): array
    {
        $retRoute = [
            "path" => $this->getRoutePath(),
            "component" => $this->getComponent(),
            "name" => $this->getRouteName(),
            "meta" => [
                "title" => $this->getName(),
                "params" => $this->getParamsObj(),
                "icon" => $this->getIcon(),
                "hidden" => $this->getVisible() ? false : true,
                "keepAlive" => $this->getKeepAlive() ? true : false,
                "blank" => $this->getBlank(),
                "isPublic" => $this->getisPublic(),
            ],
        ];
        if ($this->getRedirect())  $retRoute["redirect"] = $this->getRedirect();
        if ($this->getAlwaysShow()) $retRoute["meta"]["alwaysShow"] = true;
        return $retRoute;
    }


    public function setRouteName(?string $routeName = ""): static
    {

        $this->routeName = $routeName ?: implode(".", array_filter(explode("/", $this->getRoutePath())));
        return $this;
    }

    //根据路由路径获取路由名称,去掉路由参数，然后将'/'替换为'.';
    public function getRouteName()
    {
        // return $this->routeName;
        // //获取:左边的部分
        $rawName = explode(":", $this->getRoutePath())[0];
        $name = implode(".", array_filter(explode("/", $rawName)));
        return $this->routeName ?: $name;
    }

    public function getChildren(): Collection
    {
        return  $this->children ?: new ArrayCollection();
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
            "parentId" => $this->getParentId(),
            "name" => $this->getName(),
            "sort" => $this->getSort(),
            "icon" => $this->getIcon(),
            "title" => $this->getName(),
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
            "isPublic" => $this->getisPublic()
        ];
    }
}
